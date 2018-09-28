<?php

use Basebuilder\Scheduling\Event;
use Basebuilder\Scheduling\Schedule;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_commands_to_events(): void
    {
        $schedule = new Schedule();
        $event = $schedule->run(function () {});

        $this->assertInstanceOf(Event::class, $event);
    }

    /**
     * @test
     */
    public function it_gets_us_all_events_that_are_due_for_processing(): void
    {
        $schedule = new Schedule();
        $event = $schedule->run(function () {})->everyMinute();

        $this->assertEquals([$event], $schedule->dueEvents());
    }
}
