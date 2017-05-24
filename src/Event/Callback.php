<?php

namespace Basebuilder\Scheduling\Event;

class Callback extends BaseEvent
{
    /**
     * @var callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return !empty($this->description)
            ? $this->description
            : 'callback';
    }

    /**
     * Run the given event.
     *
     * @return mixed
     */
    public function run()
    {
        call_user_func($this->callback);
    }
}
