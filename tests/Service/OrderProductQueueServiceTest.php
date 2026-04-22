<?php

namespace ControleOnline\Queue\Tests\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\Display;
use ControleOnline\Entity\DisplayQueue;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\OrderProduct;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Queue;
use ControleOnline\Entity\Status;
use ControleOnline\Repository\QueuePeopleQueueRepository;
use ControleOnline\Service\Client\WebsocketClient;
use ControleOnline\Service\OrderProductQueueService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OrderProductQueueServiceTest extends TestCase
{
    private OrderProductQueueService $service;
    private EntityManagerInterface $entityManager;
    private QueuePeopleQueueRepository $queueRepository;
    private EntityRepository $displayQueueRepository;
    private EntityRepository $deviceConfigRepository;
    private WebsocketClient $websocketClient;

    protected function setUp(): void
    {
        $this->service = (new \ReflectionClass(OrderProductQueueService::class))->newInstanceWithoutConstructor();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queueRepository = $this->createMock(QueuePeopleQueueRepository::class);
        $this->displayQueueRepository = $this->createMock(EntityRepository::class);
        $this->deviceConfigRepository = $this->createMock(EntityRepository::class);
        $this->websocketClient = $this->createMock(WebsocketClient::class);

        $this->setObjectProperty($this->service, 'manager', $this->entityManager);
        $this->setObjectProperty($this->service, 'websocketClient', $this->websocketClient);
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
            ->with($order)
            ->willReturn(0);

        $this->service->syncByOrderStatus($order);
    }

    public function testPendingWayOrderNotifiesLinkedDisplayDevices(): void
    {
        $status = $this->createConfiguredMock(Status::class, [
            'getRealStatus' => 'pending',
            'getStatus' => 'way',
        ]);
        $provider = $this->createConfiguredMock(People::class, [
            'getId' => 3,
        ]);
        $queue = $this->createConfiguredMock(Queue::class, [
            'getId' => 10,
        ]);
        $queueEntry = $this->createConfiguredMock(OrderProductQueue::class, [
            'getQueue' => $queue,
        ]);
        $orderProduct = $this->createConfiguredMock(OrderProduct::class, [
            'getOrderProduct' => null,
            'getOrderProductQueues' => [$queueEntry],
        ]);
        $order = $this->createConfiguredMock(Order::class, [
            'getStatus' => $status,
            'getProvider' => $provider,
            'getId' => 70856,
            'getOrderProducts' => [$orderProduct],
        ]);

        $display = $this->createConfiguredMock(Display::class, [
            'getId' => 20,
        ]);
        $displayQueue = $this->createConfiguredMock(DisplayQueue::class, [
            'getDisplay' => $display,
        ]);

        $matchingDevice = $this->createConfiguredMock(Device::class, [
            'getId' => 501,
        ]);
        $ignoredDevice = $this->createConfiguredMock(Device::class, [
            'getId' => 502,
        ]);

        $matchingDeviceConfig = $this->createMock(DeviceConfig::class);
        $matchingDeviceConfig->method('getType')->willReturn('DISPLAY');
        $matchingDeviceConfig->method('getConfigs')->willReturn(['display-id' => 20]);
        $matchingDeviceConfig->method('getDevice')->willReturn($matchingDevice);

        $ignoredDeviceConfig = $this->createMock(DeviceConfig::class);
        $ignoredDeviceConfig->method('getType')->willReturn('DISPLAY');
        $ignoredDeviceConfig->method('getConfigs')->willReturn(['display-id' => 99]);
        $ignoredDeviceConfig->method('getDevice')->willReturn($ignoredDevice);

        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(function (string $className) {
                return match ($className) {
                    OrderProductQueue::class => $this->queueRepository,
                    DisplayQueue::class => $this->displayQueueRepository,
                    DeviceConfig::class => $this->deviceConfigRepository,
                    default => throw new \RuntimeException('Unexpected repository: ' . $className),
                };
            });

        $this->queueRepository
            ->expects(self::once())
            ->method('closeByOrder')
            ->with($order)
            ->willReturn(2);

        $this->displayQueueRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(self::callback(function (array $criteria) use ($queue): bool {
                return isset($criteria['queue'])
                    && is_array($criteria['queue'])
                    && count($criteria['queue']) === 1
                    && $criteria['queue'][0] === $queue;
            }))
            ->willReturn([$displayQueue]);

        $this->deviceConfigRepository
            ->expects(self::once())
            ->method('findBy')
            ->with(['people' => $provider])
            ->willReturn([$matchingDeviceConfig, $ignoredDeviceConfig]);

        $this->websocketClient
            ->expects(self::once())
            ->method('push')
            ->with(
                $matchingDevice,
                self::callback(function (string $payload): bool {
                    $events = json_decode($payload, true);
                    if (!is_array($events) || count($events) !== 2) {
                        return false;
                    }

                    $stores = array_column($events, 'store');
                    sort($stores);
                    if ($stores !== ['order_products_queue', 'queues']) {
                        return false;
                    }

                    foreach ($events as $event) {
                        if ((int) ($event['company'] ?? 0) !== 3) {
                            return false;
                        }

                        if ((int) ($event['order'] ?? 0) !== 70856) {
                            return false;
                        }

                        if ((int) ($event['queue'] ?? 0) !== 10) {
                            return false;
                        }
                    }

                    return true;
                })
            );

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
