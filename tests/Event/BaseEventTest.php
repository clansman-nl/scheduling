<?php

use Basebuilder\Scheduling\Event\BaseEvent;

class BaseEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return BaseEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent()
    {
        return $this->getMockBuilder(BaseEvent::class)->getMockForAbstractClass();
    }

    /**
     * @test
     */
    function it_can_run_every_minute()
    {
        $event = $this->getEvent();

        $event->everyMinute();

        $this->assertSame('* * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_n_minutes()
    {
        $event = $this->getEvent();
        $event->everyNMinutes(5);

        $this->assertSame("*/5 * * * * *", (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_hourly()
    {
        $event = $this->getEvent();
        $event->hourly();

        $this->assertSame('0 * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_on_every_hour()
    {
        $event = $this->getEvent();
        $event->hour(1);

        $this->assertSame('* 1 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_daily()
    {
        $event = $this->getEvent();
        $event->daily();

        $this->assertSame('0 0 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_daily_at_a_specific_time()
    {
        $event = $this->getEvent();
        $event->dailyAt('10:05');
        $this->assertSame('5 10 * * * *', (string) $event->getCronExpression());

        $event->dailyAt('10');
        $this->assertSame('0 10 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_on_specific_days()
    {
        $event = $this->getEvent();

        $event->days([1, 2 ,3 ]);
        $this->assertSame('* * * * 1,2,3 *', (string) $event->getCronExpression());

        $event->days(4, 5, 6);
        $this->assertSame('* * * * 4,5,6 *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_on_weekdays_only()
    {
        $event = $this->getEvent();
        $event->weekdays();

        $this->assertSame('* * * * 1-5 *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_weekly()
    {
        $event = $this->getEvent();
        $event->weekly();

        $this->assertSame('0 0 * * 0 *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_monthly()
    {
        $event = $this->getEvent();
        $event->monthly();

        $this->assertSame('0 0 1 * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_quarterly()
    {
        $event = $this->getEvent();
        $event->quarterly();

        $this->assertSame('0 0 1 */3 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_yearly()
    {
        $event = $this->getEvent();
        $event->yearly();

        $this->assertSame('0 0 1 1 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_allows_for_filtering()
    {
        $event = $this->getEvent();
        $event->everyMinute();

        $this->assertTrue($event->isDue());

        $event->skip(function () {
            return true;
        });

        $this->assertFalse($event->isDue());

        $event = $this->getEvent();
        $event->everyMinute();

        $this->assertTrue($event->isDue());

        $event->when(function () {
            return false;
        });

        $this->assertFalse($event->isDue());
    }
}
