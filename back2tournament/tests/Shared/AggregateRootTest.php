<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Aggregate\AggregateRoot;
use App\Shared\Event\DomainEventInterface;
use PHPUnit\Framework\TestCase;

final class AggregateRootTest extends TestCase
{
    public function test_an_aggregate_that_recorded_nothing_pulls_no_event(): void
    {
        $aggregate = new class extends AggregateRoot {
        };

        $this->assertSame([], $aggregate->pullDomainEvents());
    }

    public function test_recorded_events_are_pulled_once(): void
    {
        $aggregate = new class extends AggregateRoot {
        };
        $event = new class implements DomainEventInterface {
        };

        $aggregate->recordDomainEvent($event);

        $this->assertSame([$event], $aggregate->pullDomainEvents());
        $this->assertSame([], $aggregate->pullDomainEvents());
    }
}
