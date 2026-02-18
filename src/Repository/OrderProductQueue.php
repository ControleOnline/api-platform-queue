<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Service\StatusService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method OrderProductQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderProductQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderProductQueue[]    findAll()
 * @method OrderProductQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueuePeopleQueueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private StatusService $statusService)
    {
        parent::__construct($registry, OrderProductQueue::class);
    }

    public function cancelByOrder(Order $order)
    {

        $status = $this->statusService->discoveryRealStatus('canceled', 'canceled', 'display');

        return $this->createQueryBuilder('q')
            ->update()
            ->set('q.status', ':status')
            ->where('q.order = :order')
            ->setParameter('status', $status)
            ->setParameter('order', $order)
            ->getQuery()
            ->execute();
    }


    public function closeByOrder(Order $order)
    {

        $status = $this->statusService->discoveryRealStatus('closed', 'closed', 'display');

        return $this->createQueryBuilder('q')
            ->update()
            ->set('q.status', ':status')
            ->where('q.order = :order')
            ->setParameter('status', $status)
            ->setParameter('order', $order)
            ->getQuery()
            ->execute();
    }
}
