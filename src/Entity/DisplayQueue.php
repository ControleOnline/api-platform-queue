<?php

namespace ControleOnline\Entity;

use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;


/**
 * @ORM\EntityListeners({ControleOnline\Listener\LogListener::class})
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
 * @ORM\Table(name="display_queue", uniqueConstraints={@ORM\UniqueConstraint(name="display_id", columns={"display_id", "queue_id"})}, indexes={@ORM\Index(name="queue_id", columns={"queue_id"}), @ORM\Index(name="IDX_7EAD648851A2DF33", columns={"display_id"})})
 * @ORM\Entity
 */

class DisplayQueue
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})    
     */
    private $id;

    /**
     * @var \Display
     *
     * @ORM\ManyToOne(targetEntity="Display")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="display_id", referencedColumnName="id")
     * })
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})    
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['display' => 'exact'])]

    private $display;

    /**
     * @var \Queue
     *
     * @ORM\ManyToOne(targetEntity="Queue")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="queue_id", referencedColumnName="id")
     * })     
     * @Groups({"order:read","order_details:read","order:write","display_queue:read", "display_queue:write"})    
     */

    #[ApiFilter(filterClass: SearchFilter::class, properties: ['queue' => 'exact'])]

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
