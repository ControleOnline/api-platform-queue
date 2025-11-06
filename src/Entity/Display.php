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
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use ControleOnline\Entity\People;
use ControleOnline\Entity\DisplayQueue;
use ControleOnline\Listener\LogListener;

#[ORM\Table(name: 'display')]
#[ORM\Index(name: 'company_id', columns: ['company_id'])]

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['display:read']],
    denormalizationContext: ['groups' => ['display:write']],
    security: "is_granted('ROLE_CLIENT')",
    operations: [
        new GetCollection(security: "is_granted('ROLE_CLIENT')"),
        new Post(security: "is_granted('ROLE_CLIENT')"),
        new Get(security: "is_granted('ROLE_CLIENT')"),
        new Put(security: "is_granted('ROLE_CLIENT')"),
        new Delete(security: "is_granted('ROLE_CLIENT')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'displayQueue.queue.orderProductQueue.status.realStatus' => 'exact',
    'displayQueue.queue.orderProductQueue.status.status' => 'exact'
])]
class Display
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $id;

    #[ORM\Column(name: 'display', type: 'string', length: 50, nullable: false)]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $display;

    #[ORM\Column(name: 'display_type', type: 'string', length: 0, nullable: false, options: ['default' => "'display'"])]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $displayType = '\'display\'';

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $company;

    #[ORM\OneToMany(targetEntity: DisplayQueue::class, mappedBy: 'display')]
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

    public function getDisplay()
    {
        return $this->display;
    }

    public function setDisplay($display): self
    {
        $this->display = $display;
        return $this;
    }

    public function getDisplayType()
    {
        return $this->displayType;
    }

    public function setDisplayType($displayType): self
    {
        $this->displayType = $displayType;
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
}
