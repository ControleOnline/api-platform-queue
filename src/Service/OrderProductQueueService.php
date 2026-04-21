<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\DeviceConfig;
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
    private const ORDER_TYPE_QUOTE = 'quote';
    private const ORDER_TYPE_SALE = 'sale';

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

        if (in_array($realStatus, ['canceled', 'cancelled'], true)) {
            $this->manager
                ->getRepository(OrderProductQueue::class)
                ->cancelByOrder($order);
        }

        if ($realStatus === 'closed') {
            $this->manager
                ->getRepository(OrderProductQueue::class)
                ->closeByOrder($order);
        }

        if ($this->isDraftOrder($order)) {
            $this->manager
                ->getRepository(OrderProductQueue::class)
                ->deleteByOrder($order);
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

    private function canManageQueueForOrder(OrderEntity $order): bool
    {
        $realStatus = strtolower(trim((string) ($order->getStatus()?->getRealStatus() ?? '')));

        return $this->isProductionOrder($order)
            && !in_array($realStatus, ['closed', 'canceled', 'cancelled'], true);
    }

    private function isProductionOrder(OrderEntity $order): bool
    {
        return strtolower(trim((string) ($order->getOrderType() ?? ''))) === self::ORDER_TYPE_SALE;
    }

    private function isDraftOrder(OrderEntity $order): bool
    {
        return strtolower(trim((string) ($order->getOrderType() ?? ''))) === self::ORDER_TYPE_QUOTE;
    }

    private function pushToCompanyDevices(People $company, array $events): void
    {
        $deviceConfigs = $this->manager->getRepository(DeviceConfig::class)->findBy([
            'people' => $company,
        ]);

        $payload = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        $sentDevices = [];
        foreach ($deviceConfigs as $deviceConfig) {
            $device = $deviceConfig->getDevice();
            $deviceId = $device->getId();

            if (isset($sentDevices[$deviceId])) {
                continue;
            }

            $sentDevices[$deviceId] = true;
            $this->websocketClient->push($device, $payload);
        }
    }
}
