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
    private $request;
    private static $logger;
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private PeopleService $PeopleService,
        private WebsocketClient $websocketClient,
        private OrderPrintService $orderPrintService,
        private LoggerService $loggerService,
        RequestStack $requestStack

    ) {
        $this->request  = $requestStack->getCurrentRequest();
        self::$logger = $loggerService->getLogger('queue');
    }

    public function addProductToQueue(OrderProduct $orderProduct)
    {
        $product = $orderProduct->getProduct();
        $queue = $product->getQueue();
        if ($queue) {

            $orderProductQueue = $this->manager->getRepository(OrderProductQueue::class)->findOneBy([
                'order_product' => $orderProduct
            ]);
            if (!$orderProductQueue) {
                $orderProductQueue = new OrderProductQueue();
                $orderProductQueue->setPriority('priority');
                $orderProductQueue->setOrderProduct($orderProduct);
                $orderProductQueue->setStatus($queue->getStatusIn());
                $orderProductQueue->setQueue($queue);
                $this->manager->persist($orderProductQueue);
                $this->manager->flush();

                try {
                    $this->orderPrintService->autoPrintOrderProductQueueEntry(
                        $orderProductQueue
                    );
                } catch (\Throwable $exception) {
                    self::$logger?->error(
                        'Automatic product print failed',
                        [
                            'orderProduct' => $orderProduct->getId(),
                            'orderProductQueue' => $orderProductQueue->getId(),
                            'message' => $exception->getMessage(),
                        ]
                    );
                }
            }
        }
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
        $provider = $order->getProvider();
        if (!$provider) {
            return;
        }

        $realStatus = strtolower(trim((string) ($order->getStatus()?->getRealStatus() ?? '')));

        if ($realStatus === 'open') {
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

        $this->pushToCompanyDevices(
            $provider,
            $this->buildQueueEvents(
                $provider->getId(),
                $order->getId(),
                null,
                null,
                'order_product_queue.updated'
            )
        );
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
