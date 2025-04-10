<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use DateTime;

/**
 * @ApiResource(
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "jsonhal", "csv"={"text/csv"}},
 *          "access_control"="is_granted('ROLE_CLIENT')"
 *     },
 *     normalizationContext  ={"groups"={"order_product_queue:read"}},
 *     denormalizationContext={"groups"={"order_product_queue:write"}},
 *     attributes            ={"access_control"="is_granted('ROLE_CLIENT')"},
 *     collectionOperations  ={
 *          "get"              ={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *          },
 *     },
 *     itemOperations        ={
 *         "get"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')",
 *         },
 *         "put"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')", 
 *         },
 *         "delete"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')", 
 *         },
 *     }
 * )
 */
#[ORM\Table(name: 'order_product_queue')]
#[ORM\Index(name: 'status_id', columns: ['status_id'])]
#[ORM\Index(name: 'queue_id', columns: ['queue_id'])]
#[ORM\Index(name: 'people_id', columns: ['order_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
class OrderProductQueue
{
    /**
     * @var int
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"})
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"}) 
     */
    #[ORM\Column(name: 'priority', type: 'string', length: 0, nullable: false)]
    private $priority;

    /**
     * @var \DateTime
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"})  
     */
    #[ORM\Column(name: 'register_time', type: 'datetime', nullable: false, options: ['default' => 'current_timestamp()'])]
    private $registerTime;

    /**
     * @var \DateTime
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"}) 
     */
    #[ORM\Column(name: 'update_time', type: 'datetime', nullable: false, options: ['default' => 'current_timestamp()'])]
    private $updateTime;

    /**
     * @var OrderProduct
     *
     * @Groups({"order_product_queue:read", "order_product_queue:write"}) 
     */
    #[ApiFilter(ExistsFilter::class, properties: ['order_product.parentProduct'])]
    #[ORM\JoinColumn(name: 'order_product_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \OrderProduct::class)]

    private $order_product;

    /**
     * @var ControleOnline\Entity\Status
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['orderQueue.status.realStatus' => 'exact'])]
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status' => 'exact'])]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \ControleOnline\Entity\Status::class)]

    private $status;

    /**
     * @var \Queue
     *
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"}) 
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['queue' => 'exact'])]
    #[ORM\JoinColumn(name: 'queue_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Queue::class)]

    private $queue;


    public function __construct()
    {
        $this->registerTime = new DateTime('now');
        $this->updateTime = new DateTime('now');
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the value of priority
     */
    public function setPriority($priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get the value of registerTime
     */
    public function getRegisterTime()
    {
        return $this->registerTime;
    }

    /**
     * Set the value of registerTime
     */
    public function setRegisterTime($registerTime): self
    {
        $this->registerTime = $registerTime;

        return $this;
    }

    /**
     * Get the value of updateTime
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * Set the value of updateTime
     */
    public function setUpdateTime($updateTime): self
    {
        $this->updateTime = $updateTime;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Set the value of queue
     */
    public function setQueue($queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get the value of order_product
     */
    public function getOrderProduct()
    {
        return $this->order_product;
    }

    /**
     * Set the value of order_product
     */
    public function setOrderProduct($order_product): self
    {
        $this->order_product = $order_product;

        return $this;
    }
}
