<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\StatsCommand;

use Ds\Sequence;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\AlarmScheduler\AlarmScheduler;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\StatsCommand\RefreshTableAlarmHandler;
use Zlikavac32\BeanstalkdLibBundle\Console\TubeStatsTableDumper;

class RefreshTableAlarmHandlerSpec extends ObjectBehavior
{

    public function let(OutputInterface $output, TubeStatsTableDumper $tableDumper, Sequence $tableColumns): void
    {
        $this->beConstructedWith($output, $tableDumper, $tableColumns, 32);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(RefreshTableAlarmHandler::class);
    }

    public function it_should_refresh_and_schedule(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns,
        AlarmScheduler $alarmScheduler
    ): void {
        $alarmScheduler->schedule(32, $this)
            ->shouldBeCalled();

        $tableDumper->dump($output, $tableColumns, 0, true)
            ->shouldBeCalled();

        $this->handle($alarmScheduler);
    }

    public function it_should_refresh_with_clear_for_console_section_output_and_schedule(
        ConsoleSectionOutput $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns,
        AlarmScheduler $alarmScheduler
    ): void {
        $this->beConstructedWith($output, $tableDumper, $tableColumns, 32);

        $alarmScheduler->schedule(32, $this)
            ->shouldBeCalled();

        $output->clear()
            ->shouldBeCalled();

        $tableDumper->dump($output, $tableColumns, 0, true)
            ->shouldBeCalled();

        $this->handle($alarmScheduler);
    }

    public function it_should_sort_next(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns
    ): void {
        $tableColumns->count()
            ->willReturn(3);

        $tableDumper->dump($output, $tableColumns, 1, true)
            ->shouldBeCalled();

        $this->sortNext();
    }

    public function it_should_ignore_to_many_next_and_use_last_column(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns
    ): void {
        $tableColumns->count()
            ->willReturn(3);

        $tableDumper->dump($output, $tableColumns, 1, true)
            ->shouldBeCalled();
        $tableDumper->dump($output, $tableColumns, 2, true)
            ->shouldBeCalledTimes(2);

        $this->sortNext();
        $this->sortNext();
        $this->sortNext();
    }

    public function it_should_sort_previous(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns
    ): void {
        $tableColumns->count()
            ->willReturn(3);

        $tableDumper->dump($output, $tableColumns, 1, true)
            ->shouldBeCalled();
        $tableDumper->dump($output, $tableColumns, 0, true)
            ->shouldBeCalled();

        $this->sortNext();
        $this->sortPrevious();
    }

    public function it_should_ignore_to_many_previous_and_use_first_column(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns
    ): void {
        $tableDumper->dump($output, $tableColumns, 0, true)
            ->shouldBeCalledTimes(3);

        $this->sortPrevious();
        $this->sortPrevious();
        $this->sortPrevious();
    }

    public function it_should_toggle_order(
        OutputInterface $output,
        TubeStatsTableDumper $tableDumper,
        Sequence $tableColumns
    ): void {
        $tableDumper->dump($output, $tableColumns, 0, false)
            ->shouldBeCalled();

        $this->toggleSort();
    }
}
