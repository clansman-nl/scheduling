<?php

use Basebuilder\Scheduling\Event;
use Basebuilder\Scheduling\Schedule;

class ScheduleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function it_converts_commands_to_events()
    {
        $schedule = new Schedule();
        $event = $schedule->run('whoami');

        $this->assertInstanceOf(Event::class, $event);
    }

    /**
     * @test
     */
    function it_gets_us_all_events_that_are_due_for_processing()
    {
        $schedule = new Schedule();
        $event = $schedule->run('whoami')->everyMinute();

        $this->assertEquals([$event], $schedule->dueEvents());
    }
}
