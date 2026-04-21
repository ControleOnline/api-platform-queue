<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\Status;
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

    private function findExistingDisplayStatusId(array $realStatuses): ?int
    {
        $statusRepository = $this->getEntityManager()->getRepository(Status::class);

        foreach ($realStatuses as $realStatus) {
            $status = $statusRepository->findOneBy([
                'realStatus' => $realStatus,
                'context' => 'display',
            ]);

            if ($status instanceof Status && $status->getId()) {
                return (int) $status->getId();
            }
        }

        return null;
    }

    public function cancelByOrder(Order $order)
    {
        $statusId = $this->findExistingDisplayStatusId(['canceled', 'cancelled', 'closed']);
        if (!$statusId) {
            return 0;
        }

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
                'statusId' => $statusId,
                'orderId' => $order->getId(),
            ],
        );
    }


    public function closeByOrder(Order $order)
    {
        $fallbackStatusId = $this->findExistingDisplayStatusId(['closed', 'canceled', 'cancelled']);
        $connection = $this->getEntityManager()->getConnection();

        if (!$fallbackStatusId) {
            return $connection->executeStatement(
                'UPDATE order_product_queue opq
                    INNER JOIN order_product op ON op.id = opq.order_product_id
                    LEFT JOIN queue q ON q.id = opq.queue_id
                 SET
                    opq.status_id = q.status_out_id,
                    opq.update_time = NOW()
                 WHERE
                    op.order_id = :orderId
                    AND q.status_out_id IS NOT NULL
                    AND (
                        opq.status_id IS NULL
                        OR opq.status_id <> q.status_out_id
                    )',
                [
                    'orderId' => $order->getId(),
                ],
            );
        }

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
                'fallbackStatusId' => $fallbackStatusId,
                'orderId' => $order->getId(),
            ],
        );
    }

    public function deleteByOrder(Order $order): int
    {
        return $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE opq
             FROM order_product_queue opq
             INNER JOIN order_product op ON op.id = opq.order_product_id
             WHERE op.order_id = :orderId',
            [
                'orderId' => $order->getId(),
            ],
        );
    }
}
