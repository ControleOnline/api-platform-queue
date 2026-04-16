<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\Spool;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\OrderPrintService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintOrderProductQueueAction
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderPrintService $print,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        try {
            $orderProductQueue = $this->entityManager
                ->getRepository(OrderProductQueue::class)
                ->find($id);

            if (!$orderProductQueue instanceof OrderProductQueue) {
                return new JsonResponse(['error' => 'Order product queue not found'], 404);
            }

            $data = json_decode($request->getContent(), true) ?? [];
            $deviceId = trim((string) ($data['device'] ?? ''));
            if ($deviceId === '') {
                return new JsonResponse(['error' => 'Device not informed'], 400);
            }

            $device = $this->entityManager->getRepository(Device::class)->findOneBy([
                'device' => $deviceId
            ]);

            if (!$device instanceof Device) {
                return new JsonResponse(['error' => 'Device not found'], 404);
            }

            $printData = $this->print->generateOrderProductQueuePrintData(
                $orderProductQueue,
                $device
            );

            if (!$printData instanceof Spool) {
                return new JsonResponse(
                    ['error' => 'Nothing to print for the selected queue item'],
                    422
                );
            }

            return new JsonResponse(
                $this->hydratorService->item(
                    Spool::class,
                    $printData->getId(),
                    "spool_item:read"
                ),
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
