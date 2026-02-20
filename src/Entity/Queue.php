<?php
namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Status;
use ControleOnline\Entity\OrderProductQueue;
use ControleOnline\Entity\DisplayQueue;


#[ORM\Table(name: 'queue')]
#[ORM\Index(name: 'company_id', columns: ['company_id'])]
#[ORM\UniqueConstraint(name: 'queue', columns: ['queue', 'company_id'])]

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['queue:read']],
    denormalizationContext: ['groups' => ['queue:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Post(security: "is_granted('ROLE_CLIENT')"),
        new Get(security: "is_granted('ROLE_CLIENT')"),
        new Put(security: "is_granted('ROLE_CLIENT')"),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ]
)]
class Queue
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['display:read','display_queue:read', 'product_category:read', 'order_product_queue:read', 'product:read', 'product_group_product:read',  'order_details:read', 'order_product:read', 'order:read', 'order_details:read', 'order:write',  'queue:read', 'queue:write'])]
    private $id;

    #[ORM\Column(name: 'queue', type: 'string', length: 50, nullable: false)]
    #[Groups(['display:read','display_queue:read', 'product_category:read', 'order_product_queue:read', 'product:read', 'product_group_product:read',  'order_details:read', 'order_product:read', 'order:read', 'order_details:read', 'order:write',  'queue:read', 'queue:write'])]
    private $queue;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(name: 'status_in_id', referencedColumnName: 'id')]
    #[ApiFilter(SearchFilter::class, properties: ['status_in' => 'exact'])]
    #[Groups(['queue:write','display:read','display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $status_in;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(name: 'status_working_id', referencedColumnName: 'id')]
    #[ApiFilter(SearchFilter::class, properties: ['status_working' => 'exact'])]
    #[Groups(['queue:write','display:read','display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $status_working;

    #[ORM\ManyToOne(targetEntity: Status::class)]
    #[ORM\JoinColumn(name: 'status_out_id', referencedColumnName: 'id')]
    #[ApiFilter(SearchFilter::class, properties: ['status_out' => 'exact'])]
    #[Groups(['queue:write','display:read','display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $status_out;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ApiFilter(SearchFilter::class, properties: ['company' => 'exact'])]
    #[Groups(['display_queue:read', 'product_category:read', 'order_product_queue:read', 'product:read', 'product_group_product:read',  'order_details:read', 'order_product:read', 'order:read', 'order_details:read', 'order:write',  'queue:read', 'queue:write'])]
    private $company;

    #[ORM\OneToMany(targetEntity: OrderProductQueue::class, mappedBy: 'queue')]
    private $orderProductQueue;

    #[ORM\OneToMany(targetEntity: DisplayQueue::class, mappedBy: 'queue')]
    private $displayQueue;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;
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

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company): self
    {
        $this->company = $company;
        return $this;
    }

    public function addAOrderProductQueue(OrderProductQueue $orderProductQueue)
    {
        $this->orderProductQueue[] = $orderProductQueue;
        return $this;
    }

    public function removeOrderProductQueue(OrderProductQueue $orderProductQueue)
    {
        $this->orderProductQueue->removeElement($orderProductQueue);
    }

    public function getOrderProductQueue()
    {
        return $this->orderProductQueue;
    }

    public function addADisplayQueue(DisplayQueue $displayQueue)
    {
        $this->displayQueue[] = $displayQueue;
        return $this;
    }

    public function removeDisplayQueue(DisplayQueue $displayQueue)
    {
        $this->displayQueue->removeElement($displayQueue);
    }

    public function getDisplayQueue()
    {
        return $this->displayQueue;
    }

    public function getStatusIn()
    {
        return $this->status_in;
    }

    public function setStatusIn($status_in): self
    {
        $this->status_in = $status_in;
        return $this;
    }

    public function getStatusWorking()
    {
        return $this->status_working;
    }

    public function setStatusWorking($status_working): self
    {
        $this->status_working = $status_working;
        return $this;
    }

    public function getStatusOut()
    {
        return $this->status_out;
    }

    public function setStatusOut($status_out): self
    {
        $this->status_out = $status_out;
        return $this;
    }
}
