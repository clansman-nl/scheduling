<?php

namespace Basebuilder\Scheduling;

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
     * Creates a new Event, adds it to the schedule stack and returns you the instance so you can configure it
     *
     * @param  string $command
     * @return Event
     */
    public function run($command)
    {
        $this->events[] = $event = new Event($command);

        return $event;
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
