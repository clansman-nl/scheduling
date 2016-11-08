<?php

namespace Basebuilder\Scheduling;
use Webmozart\Assert\Assert;

/**
 * This class will allow you to register commands and retrieve all events that are due for processing
 */
class Schedule
{
    /**
     * Stack of events
     * @var Event[]
     */
    protected $events = [];

    /**
     * @var string|null
     */
    protected $name;

    public function __construct(/* string */ $name = null)
    {
        Assert::nullOrString($name);

        $this->name = $name;
    }

    /**
     * Get the name of this schedule
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Creates a new Event, adds it to the schedule stack and returns you the instance so you can configure it
     *
     * @param  string $command
     * @return Event
     */
    public function run(/* string */ $command)
    {
        Assert::stringNotEmpty($command);
        $this->events[] = $event = new Event($command);

        return $event;
    }

    /**
     * @return Event[]
     */
    public function allEvents()
    {
        return $this->events;
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @return Event[]
     */
    public function dueEvents()
    {
        return array_filter($this->events, function (Event $event) {
            return $event->isDue();
        });
    }
}
