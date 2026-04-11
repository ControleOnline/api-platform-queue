<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Service\StatusService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $status = $this->statusService->discoveryRealStatus('canceled',  'display', 'canceled');
        $connection = $this->getEntityManager()->getConnection();

        return $connection->executeStatement(
            'UPDATE order_product_queue opq
                INNER JOIN order_product op ON op.id = opq.order_product_id
             SET
                opq.status_id = :statusId,
                opq.update_time = NOW()
             WHERE
                op.order_id = :orderId
                AND (opq.status_id IS NULL OR opq.status_id <> :statusId)',
            [
                'statusId' => $status->getId(),
                'orderId' => $order->getId(),
            ],
        );
    }


    public function closeByOrder(Order $order)
    {
        $fallbackStatus = $this->statusService->discoveryRealStatus('closed',  'display', 'closed');
        $connection = $this->getEntityManager()->getConnection();

        return $connection->executeStatement(
            'UPDATE order_product_queue opq
                INNER JOIN order_product op ON op.id = opq.order_product_id
                LEFT JOIN queue q ON q.id = opq.queue_id
             SET
                opq.status_id = COALESCE(q.status_out_id, :fallbackStatusId),
                opq.update_time = NOW()
             WHERE
                op.order_id = :orderId
                AND (
                    opq.status_id IS NULL
                    OR opq.status_id <> COALESCE(q.status_out_id, :fallbackStatusId)
                )',
            [
                'fallbackStatusId' => $fallbackStatus->getId(),
                'orderId' => $order->getId(),
            ],
        );
    }
}
