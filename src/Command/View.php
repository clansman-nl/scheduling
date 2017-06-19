<?php

namespace Basebuilder\Scheduling\Command;

use Basebuilder\Scheduling\Schedule;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command lets you inspect all the scheduled events
 */
class View extends Command
{
    /**
     * @var Schedule
     */
    protected $schedule;

    /**
     * @param Schedule $schedule
     * @param null|string $name
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
        $this->setName('scheduler:view');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = Carbon::now();
        $schedule = $this->schedule;

        $output->writeln('======================================================');
        $output->writeln('# Schedule for <info>' . $schedule->getName() . '</info>');
        $output->writeln('======================================================');
        $output->writeln('');

        $events = $schedule->allEvents();

        if (empty($events)) {
            $output->writeln('<comment>No events found...</comment>');
            $output->writeln('');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['Event', 'Cron expression', 'Next run date']);

        foreach ($schedule->allEvents() as $event) {
            $exp = $event->getCronExpression();

            $table->addRow([
                (string) $event,
                (string) $exp,
                $exp->getNextRunDate($date)->format('d-m-Y H:i:s')
            ]);
        }

        $table->render();
        $output->writeln('');
    }
}
