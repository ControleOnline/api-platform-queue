<?php

namespace ControleOnline\Queue\Tests\Service;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\Status;
use ControleOnline\Repository\QueuePeopleQueueRepository;
use ControleOnline\Service\OrderProductQueueService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OrderProductQueueServiceTest extends TestCase
{
    private OrderProductQueueService $service;
    private EntityManagerInterface $entityManager;
    private QueuePeopleQueueRepository $queueRepository;

    protected function setUp(): void
    {
        $this->service = (new \ReflectionClass(OrderProductQueueService::class))->newInstanceWithoutConstructor();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queueRepository = $this->createMock(QueuePeopleQueueRepository::class);

        $this->setObjectProperty($this->service, 'manager', $this->entityManager);
    }

    public function testPendingWayOrderClosesPreparationQueues(): void
    {
        $status = $this->createConfiguredMock(Status::class, [
            'getRealStatus' => 'pending',
            'getStatus' => 'way',
        ]);
        $order = $this->createConfiguredMock(Order::class, [
            'getStatus' => $status,
        ]);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(OrderProductQueue::class)
            ->willReturn($this->queueRepository);

        $this->queueRepository
            ->expects(self::once())
            ->method('closeByOrder')
            ->with($order);

        $this->service->syncByOrderStatus($order);
    }

    public function testPendingReadyOrderKeepsPreparationQueuesOpen(): void
    {
        $status = $this->createConfiguredMock(Status::class, [
            'getRealStatus' => 'pending',
            'getStatus' => 'ready',
        ]);
        $order = $this->createConfiguredMock(Order::class, [
            'getStatus' => $status,
        ]);

        $this->entityManager
            ->expects(self::never())
            ->method('getRepository');

        $this->queueRepository
            ->expects(self::never())
            ->method('closeByOrder');

        $this->service->syncByOrderStatus($order);
    }

    private function setObjectProperty(object $object, string $propertyName, mixed $value): void
    {
        $property = new \ReflectionProperty($object, $propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
