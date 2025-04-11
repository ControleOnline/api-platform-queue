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
 *     normalizationContext  ={"groups"={"display:read"}},
 *     denormalizationContext={"groups"={"display:write"}},
 *     attributes            ={"access_control"="is_granted('ROLE_CLIENT')"},
 *     collectionOperations  ={
 *          "get"              ={
 *            "access_control"="is_granted('ROLE_CLIENT')",
 *          },
 *         "post"           ={
 *           "access_control"="is_granted('ROLE_CLIENT')", 
 *         },
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
 * @ApiFilter(
 *   SearchFilter::class, properties={
 *     "displayQueue.queue.orderProductQueue.status.realStatus": "exact",
 *     "displayQueue.queue.orderProductQueue.status.status": "exact",
 *   }
 * )
 */
#[ORM\Table(name: 'display')]
#[ORM\Index(name: 'company_id', columns: ['company_id'])]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity]
class Display
{
    /**
     * @var int
     *
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})  
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var string
     *
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})  
     */
    #[ORM\Column(name: 'display', type: 'string', length: 50, nullable: false)]
    private $display;

    /**
     * @var string
     *
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})  
     */
    #[ORM\Column(name: 'display_type', type: 'string', length: 0, nullable: false, options: ['default' => "'display'"])]
    private $displayType = '\'display\'';

    /**
     * @var \People
     *
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})  
     */
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: \People::class)]
    private $company;


    /**
     * @var \Doctrine\Common\Collections\Collection    
     */
    #[ORM\OneToMany(targetEntity: \ControleOnline\Entity\DisplayQueue::class, mappedBy: 'display')]
    private $displayQueue;



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
     * Get the value of displayType
     */
    public function getDisplayType()
    {
        return $this->displayType;
    }

    /**
     * Set the value of displayType
     */
    public function setDisplayType($displayType): self
    {
        $this->displayType = $displayType;

        return $this;
    }

    /**
     * Get the value of company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the value of company
     */
    public function setCompany($company): self
    {
        $this->company = $company;

        return $this;
    }



    /**
     * Add DisplayQueue
     *
     * @param \ControleOnline\Entity\DisplayQueue $invoice_tax
     * @return Order
     */
    public function addADisplayQueue(\ControleOnline\Entity\DisplayQueue $displayQueue)
    {
        $this->displayQueue[] = $displayQueue;

        return $this;
    }

    /**
     * Remove DisplayQueue
     *
     * @param \ControleOnline\Entity\DisplayQueue $invoice_tax
     */
    public function removeDisplayQueue(\ControleOnline\Entity\DisplayQueue $displayQueue)
    {
        $this->displayQueue->removeElement($displayQueue);
    }

    /**
     * Get DisplayQueue
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDisplayQueue()
    {
        return $this->displayQueue;
    }

}
