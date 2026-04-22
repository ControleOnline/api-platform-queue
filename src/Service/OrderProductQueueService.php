<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\DisplayQueue;
use ControleOnline\Entity\Order as OrderEntity;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\People;
use ControleOnline\Service\Client\WebsocketClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderProductQueueService
{
    private string $displayDeviceType = 'DISPLAY';
    private string $displayConfigKey = 'display-id';

    private $request;
    private static $logger;
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private PeopleService $PeopleService,
        private WebsocketClient $websocketClient,
        private LoggerService $loggerService,
        RequestStack $requestStack

    ) {
        $this->request  = $requestStack->getCurrentRequest();
        self::$logger = $loggerService->getLogger('queue');
    }

    public function addProductToQueue(OrderProduct $orderProduct)
    {
        $order = $orderProduct->getOrder();
        if (!$order instanceof OrderEntity || !$this->canManageQueueForOrder($order)) {
            return;
        }

        $product = $orderProduct->getProduct();
        $queue = $product->getQueue();
        if (!$queue) {
            return;
        }

        $targetQueueEntryCount = $this->resolveQueueEntryCount($orderProduct);
        $existingQueueEntries = $this->manager
            ->getRepository(OrderProductQueue::class)
            ->findBy(
                ['order_product' => $orderProduct],
                ['id' => 'ASC']
            );

        $removedQueueEntries = $this->removeExcessQueueEntries(
            $existingQueueEntries,
            $queue,
            $targetQueueEntryCount
        );
        if (!empty($removedQueueEntries)) {
            $existingQueueEntries = array_values(array_filter(
                $existingQueueEntries,
                static fn(OrderProductQueue $queueEntry) => !in_array(
                    $queueEntry,
                    $removedQueueEntries,
                    true
                )
            ));
        }

        $missingQueueEntryCount = $targetQueueEntryCount - count($existingQueueEntries);
        $createdQueueEntries = [];

        for ($i = 0; $i < $missingQueueEntryCount; $i++) {
            $orderProductQueue = new OrderProductQueue();
            $orderProductQueue->setPriority('priority');
            $orderProductQueue->setOrderProduct($orderProduct);
            $orderProductQueue->setStatus($queue->getStatusIn());
            $orderProductQueue->setQueue($queue);
            $this->manager->persist($orderProductQueue);
            $createdQueueEntries[] = $orderProductQueue;
        }

        if (empty($removedQueueEntries) && empty($createdQueueEntries)) {
            return;
        }

        $this->manager->flush();

        foreach ($removedQueueEntries as $removedQueueEntry) {
            $this->broadcastQueueMutation(
                $removedQueueEntry,
                'order_product_queue.deleted'
            );
        }

    }

    public function findOrderProductQueueById(int $id): ?OrderProductQueue
    {
        return $this->manager->getRepository(OrderProductQueue::class)->find($id);
    }

    private function resolveQueueEntryCount(OrderProduct $orderProduct): int
    {
        $quantity = (float) $orderProduct->getQuantity();
        if ($quantity <= 0) {
            return 0;
        }

        return max(1, (int) $quantity);
    }

    private function removeExcessQueueEntries(
        array $existingQueueEntries,
        $queue,
        int $targetQueueEntryCount
    ): array {
        $entriesToRemoveCount = count($existingQueueEntries) - $targetQueueEntryCount;
        if ($entriesToRemoveCount <= 0) {
            return [];
        }

        $initialStatusId = (int) ($queue?->getStatusIn()?->getId() ?? 0);
        $removableQueueEntries = array_values(array_filter(
            $existingQueueEntries,
            static fn(OrderProductQueue $queueEntry) =>
                (int) ($queueEntry->getStatus()?->getId() ?? 0) === $initialStatusId
        ));

        usort(
            $removableQueueEntries,
            static fn(OrderProductQueue $left, OrderProductQueue $right) =>
                (int) ($right->getId() ?? 0) <=> (int) ($left->getId() ?? 0)
        );

        $removedQueueEntries = [];

        foreach ($removableQueueEntries as $queueEntry) {
            if ($entriesToRemoveCount <= 0) {
                break;
            }

            $this->manager->remove($queueEntry);
            $removedQueueEntries[] = $queueEntry;
            $entriesToRemoveCount--;
        }

        return $removedQueueEntries;
    }

    public function postPersist(OrderProductQueue $orderProductQueue): void
    {
        $this->broadcastQueueMutation($orderProductQueue, 'order_product_queue.created');
    }

    public function postUpdate(OrderProductQueue $orderProductQueue): void
    {
        $this->broadcastQueueMutation($orderProductQueue, 'order_product_queue.updated');
    }

    public function syncByOrderStatus(OrderEntity $order): void
    {
        $realStatus = strtolower(trim((string) ($order->getStatus()?->getRealStatus() ?? '')));
        $status = strtolower(trim((string) ($order->getStatus()?->getStatus() ?? '')));

        if ($realStatus === 'open') {
            if ($this->isDraftOrder($order)) {
                $this->manager
                    ->getRepository(OrderProductQueue::class)
                    ->deleteByOrder($order);
            } elseif ($this->isProductionOrder($order)) {
                $this->ensureOrderQueueEntries($order);
            }
            return;
        }

        if ($realStatus === 'pending' && $status === 'way') {
            $this->closeOrderQueuesAndNotifyDisplays($order);
            return;
        }

        if (in_array($realStatus, ['canceled', 'cancelled'], true)) {
            $this->cancelOrderQueuesAndNotifyDisplays($order);
        }

        if ($realStatus === 'closed') {
            $this->closeOrderQueuesAndNotifyDisplays($order);
        }

        if ($this->isDraftOrder($order)) {
            $this->deleteOrderQueuesAndNotifyDisplays($order);
        }
    }

    public function ensureOrderQueueEntries(OrderEntity $order): void
    {
        foreach ($order->getOrderProducts() as $orderProduct) {
            $this->addProductToQueue($orderProduct);
        }
    }

    private function broadcastQueueMutation(OrderProductQueue $orderProductQueue, string $event): void
    {
        $order = $orderProductQueue->getOrderProduct()?->getOrder();
        $provider = $order?->getProvider() ?: $orderProductQueue->getQueue()?->getCompany();

        if (!$provider) {
            return;
        }

        $this->pushToCompanyDevices(
            $provider,
            $this->buildQueueEvents(
                $provider->getId(),
                $order?->getId(),
                $orderProductQueue->getQueue()?->getId(),
                $orderProductQueue->getId(),
                $event
            )
        );
    }

    private function buildQueueEvents(
        int $companyId,
        ?int $orderId = null,
        ?int $queueId = null,
        ?int $orderProductQueueId = null,
        string $event = 'order_product_queue.updated'
    ): array {
        $baseEvent = [
            'event' => $event,
            'company' => $companyId,
            'sentAt' => date(DATE_ATOM),
        ];

        if ($orderId) {
            $baseEvent['order'] = $orderId;
        }

        if ($queueId) {
            $baseEvent['queue'] = $queueId;
        }

        if ($orderProductQueueId) {
            $baseEvent['orderProductQueue'] = $orderProductQueueId;
        }

        return [
            array_merge(['store' => 'queues'], $baseEvent),
            array_merge(['store' => 'order_products_queue'], $baseEvent),
        ];
    }

    private function closeOrderQueuesAndNotifyDisplays(OrderEntity $order): void
    {
        $updatedRows = $this->manager
            ->getRepository(OrderProductQueue::class)
            ->closeByOrder($order);

        if ($updatedRows > 0) {
            $this->broadcastOrderQueueRefresh($order, 'order_product_queue.updated');
        }
    }

    private function cancelOrderQueuesAndNotifyDisplays(OrderEntity $order): void
    {
        $updatedRows = $this->manager
            ->getRepository(OrderProductQueue::class)
            ->cancelByOrder($order);

        if ($updatedRows > 0) {
            $this->broadcastOrderQueueRefresh($order, 'order_product_queue.updated');
        }
    }

    private function deleteOrderQueuesAndNotifyDisplays(OrderEntity $order): void
    {
        $deletedRows = $this->manager
            ->getRepository(OrderProductQueue::class)
            ->deleteByOrder($order);

        if ($deletedRows > 0) {
            $this->broadcastOrderQueueRefresh($order, 'order_product_queue.deleted');
        }
    }

    private function broadcastOrderQueueRefresh(OrderEntity $order, string $event): void
    {
        $provider = $order->getProvider();
        if (!$provider instanceof People) {
            return;
        }

        $events = $this->buildOrderQueueRefreshEvents(
            (int) $provider->getId(),
            $order,
            $event
        );

        $displayDeviceConfigs = $this->resolveDisplayDeviceConfigsForOrder($provider, $order);
        if (!empty($displayDeviceConfigs)) {
            $this->pushToDeviceConfigs($displayDeviceConfigs, $events);
            return;
        }

        $this->pushToCompanyDevices($provider, $events);
    }

    private function buildOrderQueueRefreshEvents(
        int $companyId,
        OrderEntity $order,
        string $event
    ): array {
        $queueIds = array_keys($this->resolveOrderQueues($order));
        $orderId = $this->normalizeEntityId($order->getId());

        if (empty($queueIds)) {
            return $this->buildQueueEvents($companyId, $orderId, null, null, $event);
        }

        $events = [];
        foreach ($queueIds as $queueId) {
            $events = array_merge(
                $events,
                $this->buildQueueEvents($companyId, $orderId, $queueId, null, $event)
            );
        }

        return $events;
    }

    private function resolveDisplayDeviceConfigsForOrder(
        People $company,
        OrderEntity $order
    ): array {
        $deviceConfigs = array_values(array_filter(
            $this->manager->getRepository(DeviceConfig::class)->findBy([
                'people' => $company,
            ]),
            fn($deviceConfig) => $this->isDisplayDeviceConfig($deviceConfig)
        ));

        if (empty($deviceConfigs)) {
            return [];
        }

        $displayIds = $this->resolveOrderDisplayIds($order);
        if (empty($displayIds)) {
            return $deviceConfigs;
        }

        $matchedDeviceConfigs = array_values(array_filter(
            $deviceConfigs,
            function (DeviceConfig $deviceConfig) use ($displayIds): bool {
                $configs = $deviceConfig->getConfigs(true);
                if (!is_array($configs)) {
                    return false;
                }

                $displayId = $this->normalizeEntityId(
                    $configs[$this->displayConfigKey] ?? null
                );

                return $displayId !== null && isset($displayIds[$displayId]);
            }
        ));

        return !empty($matchedDeviceConfigs) ? $matchedDeviceConfigs : $deviceConfigs;
    }

    private function resolveOrderDisplayIds(OrderEntity $order): array
    {
        $queues = $this->resolveOrderQueues($order);
        if (empty($queues)) {
            return [];
        }

        $displayRows = $this->manager->getRepository(DisplayQueue::class)->findBy([
            'queue' => array_values($queues),
        ]);

        $displayIds = [];
        foreach ($displayRows as $displayRow) {
            $displayId = $this->normalizeEntityId($displayRow->getDisplay()?->getId());
            if ($displayId !== null) {
                $displayIds[$displayId] = true;
            }
        }

        return $displayIds;
    }

    private function resolveOrderQueues(OrderEntity $order): array
    {
        $queues = [];

        foreach ($order->getOrderProducts() as $orderProduct) {
            if ($orderProduct->getOrderProduct() !== null) {
                continue;
            }

            foreach ($orderProduct->getOrderProductQueues() as $queueEntry) {
                $queue = $queueEntry->getQueue();
                $queueId = $this->normalizeEntityId($queue?->getId());

                if ($queue !== null && $queueId !== null) {
                    $queues[$queueId] = $queue;
                }
            }
        }

        return $queues;
    }

    private function isDisplayDeviceConfig(mixed $deviceConfig): bool
    {
        return $deviceConfig instanceof DeviceConfig &&
            strtoupper(trim((string) $deviceConfig->getType())) === $this->displayDeviceType;
    }

    private function normalizeEntityId(mixed $value): ?int
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            $value = $value->getId();
        }

        $normalized = preg_replace('/\D+/', '', (string) $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return (int) $normalized;
    }

    private function canManageQueueForOrder(OrderEntity $order): bool
    {
        $realStatus = strtolower(trim((string) ($order->getStatus()?->getRealStatus() ?? '')));

        return $this->isProductionOrder($order)
            && !in_array($realStatus, ['closed', 'canceled', 'cancelled'], true);
    }

    private function isProductionOrder(OrderEntity $order): bool
    {
        return strtolower(trim((string) ($order->getOrderType() ?? ''))) === OrderService::ORDER_TYPE_SALE;
    }

    private function isDraftOrder(OrderEntity $order): bool
    {
        $orderType = strtolower(trim((string) ($order->getOrderType() ?? '')));

        // Legacy quote carts must stay out of production until they are normalized to cart.
        return in_array($orderType, [
            OrderService::ORDER_TYPE_CART,
            OrderService::ORDER_TYPE_QUOTE,
        ], true);
    }

    private function pushToCompanyDevices(People $company, array $events): void
    {
        $this->pushToDeviceConfigs(
            $this->manager->getRepository(DeviceConfig::class)->findBy([
                'people' => $company,
            ]),
            $events
        );
    }

    private function pushToDeviceConfigs(array $deviceConfigs, array $events): void
    {
        $payload = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        $sentDevices = [];
        foreach ($deviceConfigs as $deviceConfig) {
            if (!$deviceConfig instanceof DeviceConfig) {
                continue;
            }

            $device = $deviceConfig->getDevice();
            $deviceId = $this->normalizeEntityId($device?->getId());
            if ($deviceId === null || isset($sentDevices[$deviceId])) {
                continue;
            }

            $sentDevices[$deviceId] = true;
            $this->websocketClient->push($device, $payload);
        }
    }
}
