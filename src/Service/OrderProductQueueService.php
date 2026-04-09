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
use ControleOnline\Event\EntityChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderProductQueueService implements EventSubscriberInterface
{
    private $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private PeopleService $PeopleService,
        private WebsocketClient $websocketClient,
        RequestStack $requestStack

    ) {
        $this->request  = $requestStack->getCurrentRequest();
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
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityChangedEvent::class => 'onEntityChanged',
        ];
    }

    public function postPersist(OrderProductQueue $orderProductQueue): void
    {
        $order = $orderProductQueue->getOrderProduct()?->getOrder();
        $provider = $order?->getProvider() ?: $orderProductQueue->getQueue()?->getCompany();

        if (!$provider) {
            return;
        }

        $this->pushToCompanyDevices($provider, [
            [
                'store' => 'queues',
                'event' => 'order_product_queue.created',
                'company' => $provider->getId(),
                'order' => $order?->getId(),
                'queue' => $orderProductQueue->getQueue()?->getId(),
                'orderProductQueue' => $orderProductQueue->getId(),
                'sentAt' => date(DATE_ATOM),
            ],
            [
                'store' => 'order_products_queue',
                'event' => 'order_product_queue.created',
                'company' => $provider->getId(),
                'order' => $order?->getId(),
                'queue' => $orderProductQueue->getQueue()?->getId(),
                'orderProductQueue' => $orderProductQueue->getId(),
                'sentAt' => date(DATE_ATOM),
            ],
        ]);
    }

    public function onEntityChanged(EntityChangedEvent $event)
    {
        $oldEntity = $event->getOldEntity();
        $entity = $event->getEntity();

        if (!$entity instanceof OrderEntity || !$oldEntity instanceof OrderEntity)
            return;

        if ($entity->getStatus()->getRealStatus() == 'canceled')
            $this->manager->getRepository(OrderProductQueue::class)->cancelByOrder($entity);

        if ($entity->getStatus()->getRealStatus() == 'closed')
            $this->manager->getRepository(OrderProductQueue::class)->closeByOrder($entity);
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
