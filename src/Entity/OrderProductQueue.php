<?php

namespace ControleOnline\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ControleOnline\Listener\LogListener;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

#[ApiResource(
    attributes: [
        'formats' => ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
        'access_control' => 'is_granted(\'ROLE_CLIENT\')'
    ],
    normalizationContext: ['groups' => ['order_product_queue:read']],
    denormalizationContext: ['groups' => ['order_product_queue:write']],
    collectionOperations: [
        'get' => [
            'access_control' => 'is_granted(\'ROLE_CLIENT\')',
        ],
    ],
    itemOperations: [
        'get' => [
            'access_control' => 'is_granted(\'ROLE_CLIENT\')',
        ],
        'put' => [
            'access_control' => 'is_granted(\'ROLE_CLIENT\')',
        ],
        'delete' => [
            'access_control' => 'is_granted(\'ROLE_CLIENT\')',
        ],
    ]
)]
#[ORM\Table(name: 'order_product_queue')]
#[ORM\Index(name: 'status_id', columns: ['status_id'])]
#[ORM\Index(name: 'queue_id', columns: ['queue_id'])]
#[ORM\Index(name: 'people_id', columns: ['order_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
class OrderProductQueue
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $id;

    #[ORM\Column(name: 'priority', type: 'string', length: 0, nullable: false)]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $priority;

    #[ORM\Column(name: 'register_time', type: 'datetime', nullable: false, options: ['default' => 'current_timestamp()'])]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $registerTime;

    #[ORM\Column(name: 'update_time', type: 'datetime', nullable: false, options: ['default' => 'current_timestamp()'])]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $updateTime;

    #[ApiFilter(ExistsFilter::class, properties: ['order_product.parentProduct'])]
    #[ORM\JoinColumn(name: 'order_product_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: OrderProduct::class)]
    #[ApiResource(normalizationContext: ['groups' => ['order_product_queue:read', 'order_product_queue:write']])]
    private $order_product;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['orderQueue.status.realStatus' => 'exact', 'status' => 'exact'])]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $status;

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['queue' => 'exact'])]
    #[ORM\JoinColumn(name: 'queue_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Queue::class)]
    #[ApiResource(normalizationContext: ['groups' => ['order:read', 'order_details:read', 'order:write', 'order_product_queue:read', 'order_product_queue:write']])]
    private $queue;

    public function __construct()
    {
        $this->registerTime = new DateTime('now');
        $this->updateTime = new DateTime('now');
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getRegisterTime()
    {
        return $this->registerTime;
    }

    public function setRegisterTime($registerTime): self
    {
        $this->registerTime = $registerTime;
        return $this;
    }

    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    public function setUpdateTime($updateTime): self
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function setQueue($queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getOrderProduct()
    {
        return $this->order_product;
    }

    public function setOrderProduct($order_product): self
    {
        $this->order_product = $order_product;
        return $this;
    }
}