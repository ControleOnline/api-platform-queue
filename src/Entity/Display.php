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
 * @ORM\Table(name="display", indexes={@ORM\Index(name="company_id", columns={"company_id"})})
 * @ORM\Entity
 */

class Display
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})   
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="display", type="string", length=50, nullable=false)
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})   
     */
    private $display;

    /**
     * @var string
     *
     * @ORM\Column(name="display_type", type="string", length=0, nullable=false, options={"default"="'display'"})
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})   
     */
    private $displayType = '\'display\'';

    /**
     * @var \People
     *
     * @ORM\ManyToOne(targetEntity="People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * })
     * @Groups({"display_queue:read","order:read","order_details:read","order:write","display:read", "display:write"})   
     */
    private $company;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\DisplayQueue", mappedBy="display")     
     */
    private $displayQueue;

    /**
     * @var ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_in_id", referencedColumnName="id")
     * })
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"})  
     */


    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status_in' => 'exact'])]

    private $status_in;
    /**
     * @var ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_working_id", referencedColumnName="id")
     * })
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"})  
     */


    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status_working' => 'exact'])]

    private $status_working;
    /**
     * @var ControleOnline\Entity\Status
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\Status")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status_out_id", referencedColumnName="id")
     * })
     * @Groups({"order:read","order_details:read","order:write","order_product_queue:read", "order_product_queue:write"})  
     */


    #[ApiFilter(filterClass: SearchFilter::class, properties: ['status_out' => 'exact'])]

    private $status_out;


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

    /**
     * Get the value of status_in
     */
    public function getStatusIn()
    {
        return $this->status_in;
    }

    /**
     * Set the value of status_in
     */
    public function setStatusIn($status_in): self
    {
        $this->status_in = $status_in;

        return $this;
    }

    /**
     * Get the value of status_working
     */
    public function getStatusWorking()
    {
        return $this->status_working;
    }

    /**
     * Set the value of status_working
     */
    public function setStatusWorking($status_working): self
    {
        $this->status_working = $status_working;

        return $this;
    }

    /**
     * Get the value of status_out
     */
    public function getStatusOut()
    {
        return $this->status_out;
    }

    /**
     * Set the value of status_out
     */
    public function setStatusOut($status_out): self
    {
        $this->status_out = $status_out;

        return $this;
    }
}
