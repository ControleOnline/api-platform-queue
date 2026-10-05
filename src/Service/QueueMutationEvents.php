<?php

namespace ControleOnline\Service;

/** Shared queue store events; batching must preserve both existing subscribers. */
final class QueueMutationEvents
{
    public static function build(
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

}
