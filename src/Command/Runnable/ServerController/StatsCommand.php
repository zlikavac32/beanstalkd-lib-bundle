<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\AlarmScheduler\AlarmScheduler;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\StatsCommand\RefreshTableAlarmHandler;
use Zlikavac32\BeanstalkdLibBundle\Console\TableDumperColumn;
use Zlikavac32\BeanstalkdLibBundle\Console\TubeStatsTableDumper;

class StatsCommand implements Command {

    /**
     * @var Client
     */
    private $client;
    /**
     * @var AlarmScheduler
     */
    private $alarmScheduler;
    /**
     * @var TubeStatsTableDumper
     */
    private $tableDumper;

    public function __construct(Client $client, TubeStatsTableDumper $tableDumper, AlarmScheduler $alarmScheduler) {
        $this->client = $client;
        $this->alarmScheduler = $alarmScheduler;
        $this->tableDumper = $tableDumper;
    }

    private function isSttyAvailable(): bool
    {
        exec('stty 2>&1', $output, $exitcode);

        return 0 === $exitcode;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void {
        $columns = new Vector([
            TableDumperColumn::TUBE_NAME(),
            TableDumperColumn::WATCHING(),
            TableDumperColumn::READY(),
            TableDumperColumn::RESERVED(),
            TableDumperColumn::DELAYED(),
            TableDumperColumn::BURIED(),
            TableDumperColumn::PAUSED_TIME(),
        ]);

        if (!isset($arguments[0]) || !$this->isSttyAvailable() || !$input->isInteractive() || !$input instanceof StreamableInputInterface) {
            $this->tableDumper->dump($output, $columns);

            return;
        }

        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->section();
        }

        $alarmHandler = new RefreshTableAlarmHandler(
            $output,
            $this->tableDumper,
            $columns,
            (int) $arguments[0]
        );

        $alarmHandler->handle($this->alarmScheduler);

        try {
            $this->waitForQuitChar($alarmHandler, $input);
        } finally {
            $this->alarmScheduler->remove($alarmHandler);
        }
    }

    private function waitForQuitChar(StatsCommand\RefreshTableAlarmHandler $alarmHandler, StreamableInputInterface $input): void {
        $sttyMode = shell_exec('stty -g');

        shell_exec('stty -icanon -echo');

        try {
            $fh = $input->getStream();
            assert($fh !== null);

            // @todo: explore idea of reinstalling signal handlers without system call restart
            stream_set_blocking($fh, false);

            try {
                while (!feof($fh)) {
                    do {
                        $c = fgetc($fh);

                        switch ($c) {
                            case 'q':
                                break 3;
                            case 'h':
                                $alarmHandler->sortPrevious();
                                break;
                            case 'l':
                                $alarmHandler->sortNext();
                                break;
                            case 'r':
                                $alarmHandler->toggleSort();
                                break;
                        }
                    } while (false !== $c);

                    sleep(1);
                }
            } finally {

                stream_set_blocking($fh, true);
            }
        } finally {
            shell_exec(sprintf('stty %s', $sttyMode));
        }
    }

    public function autoComplete(): Sequence {
        return new Vector(['stats', 'stats <REFRESH-TIME>']);
    }

    public function help(OutputInterface $output): void {
        $output->writeln(
            <<<'TEXT'
Prints current stats like number of active/reserved/buried jobs
for each tube.

Sorted column is prefixed with <info><</info> or <info>></info> where
<info><</info> is ascending order and <info>></info> is descending order.

If second argument is provided, then it's interpreted as refresh
time in seconds. Stats will be refreshed every <info><REFRESH-TIME></info> seconds.

When in refresh mode, following key bindings exist:

- <info>q</info> - quits from display
- <info>h</info> - sort by the previous column
- <info>l</info> - sort by the next column
- <info>r</info> - reverse sort
TEXT
        );
    }

    public function name(): string {
        return 'stats';
    }
}
