<?php

namespace Basebuilder\Scheduling;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

class Event
{
    /**
     * The command to run.
     * @var string
     */
    protected $command;

    /**
     * The working directory.
     * @var string
     */
    protected $cwd;

    /**
     * The user the command should run as.
     * @var string
     */
    protected $user;

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
     * Indicates if the command should not overlap itself.
     * @var bool
     */
    protected $mutuallyExclusive = false;

    /**
     * Indicates if the command should run in background.
     * @var bool
     */
    protected $runInBackground = true;

    /**
     * The array of filter callbacks. These must return true
     * @var array
     */
    protected $filters = [];

    /**
     * The array of reject callbacks.
     * @var array
     */
    protected $rejects = [];

    /**
     * The location that output should be sent to.
     * @var string
     */
    protected $output = '/dev/null';

    /**
     * The location of error output
     * @var string
     */
    protected $errorOutput = '/dev/null';

    /**
     * Indicates whether output should be appended or added (> vs >>)
     * @var bool
     */
    protected $shouldAppendOutput = true;

    /**
     * The array of callbacks to be run before the event is started.
     * @var array
     */
    protected $beforeCallbacks = [];

    /**
     * The array of callbacks to be run after the event is finished.
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * The human readable description of the event.
     * @var string
     */
    public $description;

    public function __construct(/* string */ $command)
    {
        Assert::stringNotEmpty($command);

        $this->command = $command;
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function compileCommand()
    {
        $redirect = $this->shouldAppendOutput ? '>>' : '>';

        $command = '';

        if ($this->mutuallyExclusive) {
            $command .= '(touch ' . $this->getMutexPath() . '; ';
        }

        if ($this->cwd) {
            $command .=  'cd ' . $this->cwd . '; ';
        }

        if ($this->user) {
            $command .= 'sudo -u ' . $this->user . '; ';
        }

        $command .= $this->command;

        if ($this->mutuallyExclusive) {
            $command .= ' ; rm ' . $this->getMutexPath() . ');';
        }

        // e.g. 1>> /dev/null 2>> /dev/null
        $command .= ' 1' . $redirect . ' ' . $this->output . ' 2' . $redirect . ' ' . $this->errorOutput;

        return $command;
    }

    /**
     * Run the given event.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->beforeCallbacks as $callback) {
            call_user_func($callback);
        }

        if (!$this->runInBackground) {
            $this->runCommandInForeground();
        } else {
            $this->runCommandInBackground();
        }

        foreach ($this->afterCallbacks as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Run the command in the foreground.
     *
     * @return void
     */
    protected function runCommandInForeground()
    {
        (new Process(
            trim($this->compileCommand()), $this->cwd, null, null, null
        ))->run();
    }

    /**
     * Run the command in the background.
     *
     * @return void
     */
    protected function runCommandInBackground()
    {
        (new Process(
            $this->compileCommand() . ' &', $this->cwd, null, null, null
        ))->run();
    }

    /**
     * Get the mutex path for managing concurrency
     *
     * @return string
     */
    protected function getMutexPath()
    {
        return rtrim(sys_get_temp_dir(), '/') . '/scheduled-event-' . md5($this->cwd . $this->command->getName());
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @param  string|int $safe_duration
     *
     * @return $this
     */
    public function preventOverlapping()
    {
        $this->preventOverlapping = true;

        // Skip the event if it's locked (processing)
        $this->skip(function() {
            return $this->isLocked() === false;
        });

        return $this;
    }

    /**
     * Tells you whether this event has been denied from mutual exclusiveness
     *
     * @return bool
     */
    protected function isLocked()
    {
        return file_exists($this->getMutexPath());
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
    public function unlessBetween($startTime, $endTime)
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
     * State that the command should run in the foreground
     *
     * @return $this
     */
    public function runInForeground()
    {
        $this->runInBackground = false;

        return $this;
    }

    /**
     * State that the command should run in the background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
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
     * Set which user the command should run as.
     *
     * @param  string?  $user
     * @return $this
     */
    public function asUser(/* string? */ $user)
    {
        Assert::nullOrString($user);

        $this->user = $user;

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

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @return boolean
     */
    protected function filtersPass()
    {
        foreach ($this->rejects as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Change the current working directory.
     *
     * @param  string $directory
     * @return $this
     */
    public function in(/* string */ $directory)
    {
        Assert::stringNotEmpty($directory);

        $this->cwd = $directory;

        return $this;
    }

    /**
     * Whether we append or redirect output
     *
     * @param bool $switch
     * @return $this
     */
    public function appendOutput(/* boolean */ $switch = true)
    {
        Assert::boolean($switch);

        $this->shouldAppendOutput = $switch;

        return $this;
    }

    /**
     * Set the file or location where to send file descriptor 1 to
     *
     * @param string $output
     * @return $this
     */
    public function outputTo(/* string */ $output)
    {
        Assert::stringNotEmpty($output);

        $this->output = $output;

        return $this;
    }

    /**
     * Set the file or location where to send file descriptor 2 to
     *
     * @param string $output
     * @return $this
     */
    public function errorOutputTo(/* string */ $output)
    {
        Assert::stringNotEmpty($output);

        $this->errorOutput = $output;

        return $this;
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
     * @return Event
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
     * @return Event
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
     * @param  \Closure  $callback
     * @return $this
     */
    public function when(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }
}
