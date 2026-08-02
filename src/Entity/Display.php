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


#[ORM\Table(name: 'display')]
#[ORM\Index(name: 'company_id', columns: ['company_id'])]

#[ORM\Entity]
#[ApiResource(
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => 'text/csv'],
    normalizationContext: ['groups' => ['display:read']],
    denormalizationContext: ['groups' => ['display:write']],
    security: "is_granted('ROLE_HUMAN')",
    operations: [
        new GetCollection(security: "is_granted('ROLE_HUMAN')"),
        new Post(security: "is_granted('ROLE_HUMAN')"),
        new Get(security: "is_granted('ROLE_HUMAN')"),
        new Put(security: "is_granted('ROLE_HUMAN')"),
        new Delete(security: "is_granted('ROLE_HUMAN')")
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'displayQueue.queue.orderProductQueue.status.realStatus' => 'exact',
    'displayQueue.queue.orderProductQueue.status.status' => 'exact'
])]
class Display
{
    public const QUEUE_IDENTIFICATION_NONE = 'none';
    public const QUEUE_IDENTIFICATION_NAME = 'name';
    public const QUEUE_IDENTIFICATION_SHORT_LABEL = 'short_label';
    public const QUEUE_IDENTIFICATION_ICON = 'icon';
    public const STATUS_INDICATOR_BULLET = 'bullet';
    public const STATUS_INDICATOR_LINE = 'line';

    public const DISPLAY_TYPE_PRODUCTION = 'production';
    public const DISPLAY_TYPE_CONFERENCE = 'conference';
    public const DISPLAY_TYPE_TRACKING = 'tracking';

    private const LEGACY_DISPLAY_TYPES = [
        'products' => self::DISPLAY_TYPE_PRODUCTION,
        'orders' => self::DISPLAY_TYPE_CONFERENCE,
        'tv' => self::DISPLAY_TYPE_TRACKING,
    ];

    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $id;

    #[ORM\Column(name: 'display', type: 'string', length: 50, nullable: false)]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $display;

    #[ORM\Column(name: 'display_type', type: 'string', length: 0, nullable: false, options: ['default' => self::DISPLAY_TYPE_PRODUCTION])]
    #[Groups(['display_queue:read', 'order:read', 'order_details:read', 'order:write',  'display:read', 'display:write'])]
    private $displayType = self::DISPLAY_TYPE_PRODUCTION;

    #[ORM\Column(name: 'queue_identification_mode', type: 'string', length: 16, nullable: false, options: ['default' => self::QUEUE_IDENTIFICATION_SHORT_LABEL])]
    #[Groups(['display:read', 'display:write'])]
    private string $queueIdentificationMode = self::QUEUE_IDENTIFICATION_SHORT_LABEL;

    #[ORM\Column(name: 'status_indicator_mode', type: 'string', length: 16, nullable: false, options: ['default' => self::STATUS_INDICATOR_BULLET])]
    #[Groups(['display:read', 'display:write'])]
    private string $statusIndicatorMode = self::STATUS_INDICATOR_BULLET;

    #[ORM\Column(name: 'show_unit_quantity', type: 'boolean', nullable: false, options: ['default' => '0'])]
    #[Groups(['display:read', 'display:write'])]
    private bool $showUnitQuantity = false;

    #[ORM\Column(name: 'show_group_names', type: 'boolean', nullable: false, options: ['default' => '0'])]
    #[Groups(['display:read', 'display:write'])]
    private bool $showGroupNames = false;

    #[ORM\ManyToOne(targetEntity: People::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    #[Groups(['display_queue:read', 'order:read', 'order:write',  'display:read', 'display:write'])]
    private $company;

    #[ORM\OneToMany(targetEntity: DisplayQueue::class, mappedBy: 'display')]
    #[Groups(['display:read', 'display:write'])]
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
        return self::normalizeDisplayType($this->displayType);
    }

    public function setDisplayType($displayType): self
    {
        $this->displayType = self::normalizeDisplayType($displayType);
        return $this;
    }

    public static function normalizeDisplayType($displayType): string
    {
        $type = strtolower(trim((string) $displayType));

        if ($type === '') {
            return self::DISPLAY_TYPE_PRODUCTION;
        }

        return self::LEGACY_DISPLAY_TYPES[$type] ?? $type;
    }

    public function getQueueIdentificationMode(): string
    {
        return $this->queueIdentificationMode;
    }

    public function setQueueIdentificationMode(string $mode): self
    {
        $allowed = [self::QUEUE_IDENTIFICATION_NONE, self::QUEUE_IDENTIFICATION_NAME, self::QUEUE_IDENTIFICATION_SHORT_LABEL, self::QUEUE_IDENTIFICATION_ICON];
        $this->queueIdentificationMode = in_array($mode, $allowed, true)
            ? $mode
            : self::QUEUE_IDENTIFICATION_SHORT_LABEL;
        return $this;
    }

    public function getStatusIndicatorMode(): string
    {
        return $this->statusIndicatorMode;
    }

    public function setStatusIndicatorMode(string $mode): self
    {
        $this->statusIndicatorMode = $mode === self::STATUS_INDICATOR_LINE
            ? self::STATUS_INDICATOR_LINE
            : self::STATUS_INDICATOR_BULLET;
        return $this;
    }

    public function getShowUnitQuantity(): bool
    {
        return $this->showUnitQuantity;
    }

    public function setShowUnitQuantity(bool $showUnitQuantity): self
    {
        $this->showUnitQuantity = $showUnitQuantity;
        return $this;
    }

    public function getShowGroupNames(): bool
    {
        return $this->showGroupNames;
    }

    public function setShowGroupNames(bool $showGroupNames): self
    {
        $this->showGroupNames = $showGroupNames;
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
