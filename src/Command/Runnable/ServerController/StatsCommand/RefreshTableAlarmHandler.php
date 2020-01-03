<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\StatsCommand;

use Ds\Sequence;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\AlarmScheduler\AlarmHandler;
use Zlikavac32\AlarmScheduler\AlarmScheduler;
use Zlikavac32\BeanstalkdLibBundle\Console\TubeStatsTableDumper;

class RefreshTableAlarmHandler implements AlarmHandler
{

    private OutputInterface $output;

    private int $sleepTime;

    private TubeStatsTableDumper $tableDumper;

    private Sequence $tableColumns;
    private int $sortColumn = 0;
    private bool $descending = true;

    public function __construct(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns,
        int $sleepTime
    ) {
        $this->output = $output;
        $this->sleepTime = $sleepTime;
        $this->tableDumper = $tableDumper;
        $this->tableColumns = $tableColumns;
    }

    public function handle(AlarmScheduler $scheduler): void
    {
        $this->refresh();

        $scheduler->schedule($this->sleepTime, $this);
    }

    private function refresh(): void
    {
        if ($this->output instanceof ConsoleSectionOutput) {
            $this->output->clear();
        }

        $this->tableDumper->dump($this->output, $this->tableColumns, $this->sortColumn, $this->descending);
    }

    public function sortNext(): void
    {
        $this->sortColumn = min($this->tableColumns->count() - 1, $this->sortColumn + 1);

        $this->refresh();
    }

    public function sortPrevious(): void
    {
        $this->sortColumn = max(0, $this->sortColumn - 1);

        $this->refresh();
    }

    public function toggleSort(): void
    {
        $this->descending = !$this->descending;

        $this->refresh();
    }
}