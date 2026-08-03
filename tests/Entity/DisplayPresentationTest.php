<?php

namespace ControleOnline\Queue\Tests\Entity;

use ControleOnline\Entity\Display;
use ControleOnline\Entity\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Attribute\Groups;

class DisplayPresentationTest extends TestCase
{
    public function testCompactDisplayDefaults(): void
    {
        $display = new Display();

        self::assertSame(Display::QUEUE_IDENTIFICATION_SHORT_LABEL, $display->getQueueIdentificationMode());
        self::assertSame(Display::STATUS_INDICATOR_BULLET, $display->getStatusIndicatorMode());
        self::assertFalse($display->getShowUnitQuantity());
        self::assertFalse($display->getShowGroupNames());
    }

    public function testQueueAcceptsOptionalShortIdentityAndIcon(): void
    {
        $queue = (new Queue())
            ->setQueue('Gyros Fritadeira')
            ->setShortLabel('Fritadeira')
            ->setIcon('french-fries');

        self::assertSame('Fritadeira', $queue->getShortLabel());
        self::assertSame('french-fries', $queue->getIcon());
    }

    public function testPresentationFieldsAreAvailableToTheDisplayEditor(): void
    {
        foreach (['queueIdentificationMode', 'statusIndicatorMode', 'showUnitQuantity', 'showGroupNames'] as $property) {
            $groups = (new \ReflectionProperty(Display::class, $property))
                ->getAttributes(Groups::class)[0]
                ->newInstance()
                ->getGroups();

            self::assertContains('display:read', $groups);
            self::assertContains('display:write', $groups);
        }
    }
}
