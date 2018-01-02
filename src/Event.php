<?php

namespace Basebuilder\Scheduling;

use Cron\CronExpression;

interface Event
{
    /**
     * @return string|null
     */
    public function getName();

    /**
     * @return string
     */
    public function __toString();

    /**
     * @param string $description
     * @return $this
     */
    public function describe($description);

    /**
     * Run the given event.
     *
     * @return mixed
     */
    public function run();

    /**
     * Schedule the event to run between start and end time.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function between($startTime, $endTime);

    /**
     * Schedule the event to not run between start and end time.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return $this
     */
    public function notBetween($startTime, $endTime);

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @return $this
     */
    public function timezone(\DateTimeZone $timezone);

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @return bool
     */
    public function isDue();

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron(/* string */ $expression);

    /**
     * @return CronExpression
     */
    public function getCronExpression();

    /**
     * Change the minute when the job should run (0-59, *, *\/2 etc)
     *
     * @param  string|int $minute
     * @return $this
     */
    public function minute($minute);

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute();

    /**
     * Schedule this event to run every 5 minutes
     *
     * @return Event
     */
    public function everyFiveMinutes();

    /**
     * Schedule the event to run every N minutes
     *
     * @param  int $n
     * @return $this
     */
    public function everyNMinutes(/* int */ $n);

    /**
     * Set the hour when the job should run (0-23, *, *\/2, etc)
     *
     * @param  string|int $hour
     * @return Event
     */
    public function hour($hour);

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly();

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily();

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt(/* string */ $time);

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days);

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays();

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays();

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays();

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays();

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays();

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays();

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays();

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays();

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly();

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0');

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly();

    /**
     * Schedule the event to run monthly on a given day and time.
     *
     * @param int  $day
     * @param string  $time
     * @return $this
     */
    public function monthlyOn($day = 1, $time = '0:0');

    /**
     * Schedule the event to run quarterly.
     *
     * @return $this
     */
    public function quarterly();

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly();

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function when(callable $callback);

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function skip(callable $callback);

    /**
     * Register a callback to be called before the operation.
     *
     * @param callable $callback
     * @return $this
     */
    public function before(callable $callback);

    /**
     * Register a callback to be called after the operation.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function after(callable $callback);
}
