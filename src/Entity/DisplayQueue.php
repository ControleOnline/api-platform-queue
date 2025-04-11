<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;


/**
 * @ApiResource(
 *     attributes={
 *          "formats"={"jsonld", "json", "html", "jsonhal", "csv"={"text/csv"}},
 *          "access_control"="is_granted('ROLE_CLIENT')"
 *     },
 *     normalizationContext  ={"groups"={"display_queue:read"}},
 *     denormalizationContext={"groups"={"display_queue:write"}},
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
#[ORM\Table(name: 'display_queue')]
#[ORM\Index(name: 'queue_id', columns: ['queue_id'])]
#[ORM\Index(name: 'IDX_7EAD648851A2DF33', columns: ['display_id'])]
#[ORM\UniqueConstraint(name: 'display_id', columns: ['display_id', 'queue_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
class DisplayQueue
{
    /**
     * @var int
     *
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})   
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \Display
     *
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})   
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['display' => 'exact'])]
    #[ORM\JoinColumn(name: 'display_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Display::class)]

    private $display;

    /**
     * @var \Queue
     *
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})   
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['queue' => 'exact'])]
    #[ORM\JoinColumn(name: 'queue_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \Queue::class)]

    private $queue;



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
     * Get the value of display
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set the value of display
     */
    public function setDisplay($display): self
    {
        $this->display = $display;

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
}
