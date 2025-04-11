<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Invoice;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\OrderProductQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
 AS Security;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderProductQueueService
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
}
