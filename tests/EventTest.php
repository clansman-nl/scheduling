<?php

use Basebuilder\Scheduling\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function it_can_compile_the_command()
    {
        $event = new Event('php -i');

        $this->assertSame("sh -c 'php -i 1>> '/dev/null' 2>> '/dev/null''", $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_switch_user()
    {
        $event = new Event('php -i');
        $event->asUser('foo');

        $this->assertSame("sudo -u foo -- sh -c 'php -i 1>> '/dev/null' 2>> '/dev/null''", $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_switch_directory()
    {
        $event = new Event('php -i');
        $event->in('/foo/bar');

        $this->assertSame("cd /foo/bar; sh -c 'php -i 1>> '/dev/null' 2>> '/dev/null''", $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_append_output()
    {
        $event = new Event('php -i');
        $event->appendOutput(true);

        $this->assertSame("sh -c 'php -i 1>> '/dev/null' 2>> '/dev/null''", $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_overwrite_output()
    {
        $event = new Event('php -i');
        $event->appendOutput(false);

        $this->assertSame("sh -c 'php -i 1> '/dev/null' 2> '/dev/null''", $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_run_every_minute()
    {
        $event = new Event('php -i');
        $event->everyMinute();

        $this->assertSame('* * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_n_minutes()
    {
        $event = new Event('php -i');
        $event->everyNMinutes(5);

        $this->assertSame("*/5 * * * * *", (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_hourly()
    {
        $event = new Event('php -i');
        $event->hourly();

        $this->assertSame('0 * * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_on_every_hour()
    {
        $event = new Event('php -i');
        $event->hour(1);

        $this->assertSame('* 1 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_daily()
    {
        $event = new Event('php -i');
        $event->daily();

        $this->assertSame('0 0 * * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_daily_at_a_specific_time()
    {
        $event = new Event('php -i');
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
        $event = new Event('php -i');

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
        $event = new Event('php -i');
        $event->weekdays();

        $this->assertSame('* * * * 1-5 *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_weekly()
    {
        $event = new Event('php -i');
        $event->weekly();

        $this->assertSame('0 0 * * 0 *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_monthly()
    {
        $event = new Event('php -i');
        $event->monthly();

        $this->assertSame('0 0 1 * * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_quarterly()
    {
        $event = new Event('php -i');
        $event->quarterly();

        $this->assertSame('0 0 1 */3 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_can_run_yearly()
    {
        $event = new Event('php -i');
        $event->yearly();

        $this->assertSame('0 0 1 1 * *', (string) $event->getCronExpression());
    }

    /**
     * @test
     */
    function it_allows_for_filtering()
    {
        $event = new Event('php -i');
        $event->everyMinute();

        $this->assertTrue($event->isDue());

        $event->skip(function () {
            return true;
        });

        $this->assertFalse($event->isDue());

        $event = new Event('php -i');
        $event->everyMinute();

        $this->assertTrue($event->isDue());

        $event->when(function () {
            return false;
        });

        $this->assertFalse($event->isDue());
    }

    /**
     * @group fix
     */
    function it_can_prevent_overlapping()
    {
        $event = new Event('php -i');
        $event->preventOverlapping();

        preg_match('/\(touch \w*; php -i; rm \w*\); 1>> /dev/null 2>> /dev/null/', $event->compileCommand());
    }
}
