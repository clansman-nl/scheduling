<?php

namespace Basebuilder\Scheduling\Command;

use Basebuilder\Scheduling\Event\Process;
use Basebuilder\Scheduling\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command runs all scheduled tasks that are due for execution, replacing the need for multiple CRON jobs
 */
class Run extends Command
{
    /**
     * @var Schedule
     */
    protected $schedule;

    protected $minimumVerbosity = OutputInterface::VERBOSITY_VERBOSE;

    /**
     * @param Schedule    $schedule
     * @param string|null $name
     */
    public function __construct(Schedule $schedule, $name = null)
    {
        $this->schedule = $schedule;
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('scheduler:run');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schedule = $this->schedule;
        $events   = $schedule->dueEvents();

        if ($output->getVerbosity() >= $this->minimumVerbosity) {
            $output->writeln(
                '======================================================' . PHP_EOL .
                '# Running schedule for <info>' . $schedule->getName() . '</info>' . PHP_EOL .
                '======================================================'
            );
        }

        foreach ($events as $event) {
            if ($output->getVerbosity() >= $this->minimumVerbosity) {
                $output->writeln('Running event <info>"' . (string)$event . '"</info>');
            }

            $result = $event->run();
            if ($event instanceof Process)  {
                $this->handleProcessOutput($result, $output);
            }
        }
    }

    protected function handleProcessOutput(\Symfony\Component\Process\Process $process, OutputInterface $output)
    {
        if ($process->isTerminated() && $output->getVerbosity() >= $this->minimumVerbosity) {
            $tag = $process->isSuccessful() ? 'info' : 'error';
            $output->writeln('Exit code: <' . $tag . '>' . $process->getExitCode() . '</' . $tag . '>');
        }
    }
}
