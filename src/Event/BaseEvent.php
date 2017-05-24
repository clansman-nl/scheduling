<?php

namespace Basebuilder\Scheduling\Event;

use Basebuilder\Scheduling\Event;
use Carbon\Carbon;
use Cron\CronExpression;
use Webmozart\Assert\Assert;

abstract class BaseEvent implements Event
{
    /**
     * The cron expression representing the event's frequency.
     * @var string
     */
    protected $expression = '* * * * * *';

    /**
     * The timezone the date should be evaluated on.
     * @var \DateTimeZone|string
     */
    protected $timezone;

    /**
     * The array of filter callbacks. These must return true
     * @var callable[]
     */
    protected $filters = [];

    /**
     * The array of callbacks to be run before the event is started.
     * @var callable[]
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     * @var callable[]
     */
    protected $afterCallbacks = [];

    /**
     * The array of reject callbacks.
     * @var callable[]
     */
    protected $rejects = [];

    /**
     * @var string
     */
    protected $description;

    /**
     * @param string $description
     * @return $this
     */
    public function describe($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function between($startTime, $endTime)
    {
        return $this->when($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to not run between start and end time.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function notBetween($startTime, $endTime)
    {
        return $this->skip($this->inTimeInterval($startTime, $endTime));
    }

    /**
     * Schedule the event to run between start and end time.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return \Closure
     */
    private function inTimeInterval($startTime, $endTime)
    {
        return function () use ($startTime, $endTime) {
            $now = Carbon::now()->getTimestamp();
            return $now >= strtotime($startTime) && $now <= strtotime($endTime);
        };
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @return $this
     */
    public function timezone(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @return bool
     */
    public function isDue()
    {
        return $this->expressionPasses() && $this->filtersPass();
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return boolean
     */
    protected function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return $this->getCronExpression()->isDue($date->toDateTimeString());
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @return boolean
     */
    protected function filtersPass()
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron(/* string */ $expression)
    {
        Assert::stringNotEmpty($expression);

        $this->expression = $expression;

        return $this;
    }

    /**
     * @return CronExpression
     */
    public function getCronExpression()
    {
        return CronExpression::factory($this->expression);
    }

    /**
     * Change the minute when the job should run (0-59, *, *\/2 etc)
     *
     * @param  string|int $minute
     * @return $this
     */
    public function minute($minute)
    {
        return $this->spliceIntoPosition(1, $minute);
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->minute('*');
    }

    /**
     * Schedule this event to run every 5 minutes
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->everyNMinutes(5);
    }

    /**
     * Schedule the event to run every N minutes
     *
     * @param  int $n
     * @return $this
     */
    public function everyNMinutes(/* int */ $n)
    {
        Assert::integer($n);

        return $this->minute("*/{$n}");
    }

    /**
     * Set the hour when the job should run (0-23, *, *\/2, etc)
     *
     * @param  string|int $hour
     * @return $this
     */
    public function hour($hour)
    {
        return $this->spliceIntoPosition(2, $hour);
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, '*');
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt(/* string */ $time)
    {
        Assert::stringNotEmpty($time);

        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
            ->spliceIntoPosition(1, count($segments) == 2 ? (int) $segments[1] : '0');
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(5, 0);
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);
        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1);
    }

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int  $day
     * @param string  $time
     * @return $this
     */
    public function monthlyOn($day = 1, $time = '0:0')
    {
        $this->dailyAt($time);
        return $this->spliceIntoPosition(3, $day);
    }

    /**
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, '*/3');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, 1);
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param  int  $position
     * @param  string  $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);
        $segments[$position - 1] = $value;
        return $this->cron(implode(' ', $segments));
    }


    /**
     * Register a callback to further filter the schedule.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function when(callable $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function skip(callable $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called before the operation.
     *
     * @param callable $callback
     * @return $this
     */
    public function before(callable $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }
}
