<?php

namespace Basebuilder\Scheduling\Event;

use Symfony\Component\Process\Process as OsProcess;
use Symfony\Component\Process\ProcessUtils;
use Webmozart\Assert\Assert;

class Process extends BaseEvent
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

    public function __construct(/* string */ $command)
    {
        Assert::stringNotEmpty($command);

        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getCommand();
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function compileCommand()
    {
        $redirect    = $this->shouldAppendOutput ? '>>' : '>';
        $output      = ProcessUtils::escapeArgument($this->output);
        $errorOutput = ProcessUtils::escapeArgument($this->errorOutput);

        // e.g. 1>> /dev/null 2>> /dev/null
        $outputRedirect = ' 1' . $redirect . ' ' . $output . ' 2' . $redirect . ' ' . $errorOutput;

        $parts = [];

        if ($this->cwd) {
            $parts[] =  'cd ' . $this->cwd . ';';
        }

        if ($this->user) {
            $parts[] = 'sudo -u ' . $this->user . ' --';
        }

        $wrapped = $this->mutuallyExclusive
            ? '(touch ' . $this->getMutexPath() . '; ' . $this->command . '; rm ' . $this->getMutexPath() . ')' . $outputRedirect
            : $this->command . $outputRedirect;

        $parts[] = "sh -c '{$wrapped}'";

        $command = implode(' ', $parts);

        return $command;
    }

    /**
     * Run the given event.
     *
     * @return OsProcess
     */
    public function run()
    {
        foreach ($this->beforeCallbacks as $callback) {
            call_user_func($callback);
        }

        if (!$this->runInBackground) {
            $process = $this->runCommandInForeground();
        } else {
            $process = $this->runCommandInBackground();
        }

        foreach ($this->afterCallbacks as $callback) {
            call_user_func($callback);
        }

        return $process;
    }

    /**
     * Run the command in the foreground.
     *
     * @return OsProcess
     */
    protected function runCommandInForeground()
    {
        $process = new OsProcess($this->compileCommand(), $this->cwd, null, null, null);
        $process->run();

        return $process;
    }

    /**
     * Run the command in the background.
     *
     * @return OsProcess
     */
    protected function runCommandInBackground()
    {
        $process = new OsProcess($this->compileCommand(), $this->cwd, null, null, null);
        $process->start();

        return $process;
    }

    /**
     * Get the mutex path for managing concurrency
     *
     * @return string
     */
    protected function getMutexPath()
    {
        return rtrim(sys_get_temp_dir(), '/') . '/scheduled-event-' . md5($this->cwd . $this->command);
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @return $this
     */
    public function preventOverlapping()
    {
        $this->mutuallyExclusive = true;

        // Skip the event if it's locked (processing)
        $this->skip(function() {
            return $this->isLocked();
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
}
