<?php

use Basebuilder\Scheduling\Event\BaseEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BaseEventTest extends TestCase
{
    /**
     * @return MockObject|BaseEvent
     * @throws ReflectionException
     */
    protected function getEvent(): BaseEvent
    {
        return $this->getMockForAbstractClass(BaseEvent::class);
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_every_minute(): void
    {
        $event = $this->getEvent();

        $event->everyMinute();

        $this->assertSame('* * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_n_minutes(): void
    {
        $event = $this->getEvent();
        $event->everyNMinutes(5);

        $this->assertSame("*/5 * * * * *", (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_hourly(): void
    {
        $event = $this->getEvent();
        $event->hourly();

        $this->assertSame('0 * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_on_every_hour(): void
    {
        $event = $this->getEvent();
        $event->hour(1);

        $this->assertSame('* 1 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_daily(): void
    {
        $event = $this->getEvent();
        $event->daily();

        $this->assertSame('0 0 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_daily_at_a_specific_time(): void
    {
        $event = $this->getEvent();
        $event->dailyAt('10:05');
        $this->assertSame('5 10 * * * *', (string) $event->getCronExpression());

        $event->dailyAt('10');
        $this->assertSame('0 10 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_on_specific_days(): void
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
     * @throws ReflectionException
     */
    public function it_can_run_monthly(): void
    {
        $event = $this->getEvent();
        $event->monthly();

        $this->assertSame('0 0 1 * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_quarterly(): void
    {
        $event = $this->getEvent();
        $event->quarterly();

        $this->assertSame('0 0 1 */3 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_can_run_yearly(): void
    {
        $event = $this->getEvent();
        $event->yearly();

        $this->assertSame('0 0 1 1 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function it_allows_for_filtering(): void
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
