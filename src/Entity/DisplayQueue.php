<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ControleOnline\Listener\LogListener;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Put(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')')
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['display_queue:read']],
    denormalizationContext: ['groups' => ['display_queue:write']]
)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['display' => 'exact', 'queue' => 'exact'])]
#[ORM\Table(name: 'display_queue')]
#[ORM\Index(name: 'queue_id', columns: ['queue_id'])]
#[ORM\Index(name: 'IDX_7EAD648851A2DF33', columns: ['display_id'])]
#[ORM\UniqueConstraint(name: 'display_id', columns: ['display_id', 'queue_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
class DisplayQueue
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['order:read', 'order_details:read', 'order:write', 'display_queue:read', 'display_queue:write'])]
    private int $id;

    #[ORM\JoinColumn(name: 'display_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Display::class)]
    #[Groups(['order:read', 'order_details:read', 'order:write', 'display_queue:read', 'display_queue:write'])]
    private Display $display;

    #[ORM\JoinColumn(name: 'queue_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Queue::class)]
    #[Groups(['order:read', 'order_details:read', 'order:write', 'display_queue:read', 'display_queue:write'])]
    private Queue $queue;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDisplay(): Display
    {
        return $this->display;
    }

    public function setDisplay(Display $display): self
    {
        $this->display = $display;
        return $this;
    }

    public function getQueue(): Queue
    {
        return $this->queue;
    }

    public function setQueue(Queue $queue): self
    {
        $this->queue = $queue;
        return $this;
    }
}