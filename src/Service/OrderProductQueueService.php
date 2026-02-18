<?php

namespace ControleOnline\Service;

use App\Library\Nuvemshop\Model\Order;
use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\OrderProductQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Doctrine\ORM\QueryBuilder;
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

    public function onEntityChanged(EntityChangedEvent $event)
    {
        $oldEntity = $event->getOldEntity();
        $entity = $event->getEntity();

        if (!$entity instanceof Order || !$oldEntity instanceof Order)
            return;

        if ($entity->getStatus()->getRealStatus() == 'canceled')
            $this->manager->getRepository(OrderProductQueue::class)->cancelByOrder($entity);

        if ($entity->getStatus()->getRealStatus() == 'closed')
            $this->manager->getRepository(OrderProductQueue::class)->closeByOrder($entity);
    }
}
