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

        $this->assertSame('php -i 1>> /dev/null 2>> /dev/null', $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_switch_user()
    {
        $event = new Event('php -i');
        $event->asUser('foo');

        $this->assertSame('sudo -u foo; php -i 1>> /dev/null 2>> /dev/null', $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_switch_directory()
    {
        $event = new Event('php -i');
        $event->in('/foo/bar');

        $this->assertSame('cd /foo/bar; php -i 1>> /dev/null 2>> /dev/null', $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_append_output()
    {
        $event = new Event('php -i');
        $event->appendOutput(true);

        $this->assertSame('php -i 1>> /dev/null 2>> /dev/null', $event->compileCommand());
    }

    /**
     * @test
     */
    function it_can_overwrite_output()
    {
        $event = new Event('php -i');
        $event->appendOutput(false);

        $this->assertSame('php -i 1> /dev/null 2> /dev/null', $event->compileCommand());
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
