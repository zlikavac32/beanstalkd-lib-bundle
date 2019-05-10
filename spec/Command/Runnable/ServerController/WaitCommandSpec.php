<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\ServerMetrics;
use Zlikavac32\BeanstalkdLib\ServerStats;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\WaitCommand;

class WaitCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client, 0);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(WaitCommand::class);
    }

    public function it_should_return_autocomplete_list(): void
    {
        $this->autoComplete()
            ->sorted()
            ->toArray()
            ->shouldReturn(['wait']);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::containingString('0 second(s)'))
            ->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_wait(): void
    {
        $this->name()
            ->shouldReturn('wait');
    }

    public function it_should_wait_until_there_are_no_reserved_jobs(
        Client $client,
        ServerStats $serverStats,
        ServerMetrics $firstServerMetrics,
        ServerMetrics $secondServerMetrics,
        ServerMetrics $thirdServerMetrics,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->stats()
            ->willReturn($serverStats);

        $serverStats->serverMetrics()
            ->willReturn($firstServerMetrics, $secondServerMetrics, $thirdServerMetrics);

        $firstServerMetrics->numberOfReservedJobs()
            ->willReturn(5);
        $secondServerMetrics->numberOfReservedJobs()
            ->willReturn(2);
        $thirdServerMetrics->numberOfReservedJobs()
            ->willReturn(0);

        $this->run([], $input, $helperSet, $output);
    }

    public function it_should_only_output_remaining_jobs_if_it_changed_since_last_iteration(
        Client $client,
        ServerStats $serverStats,
        ServerMetrics $firstServerMetrics,
        ServerMetrics $secondServerMetrics,
        ServerMetrics $thirdServerMetrics,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->stats()
            ->willReturn($serverStats);

        $serverStats->serverMetrics()
            ->willReturn($firstServerMetrics, $secondServerMetrics, $thirdServerMetrics);

        $output->writeln('5 remaining')
            ->shouldBeCalledTimes(1);

        $firstServerMetrics->numberOfReservedJobs()
            ->willReturn(5);
        $secondServerMetrics->numberOfReservedJobs()
            ->willReturn(5);
        $thirdServerMetrics->numberOfReservedJobs()
            ->willReturn(0);

        $this->run([], $input, $helperSet, $output);
    }

    // @todo: write test that we really called sleep, but that will have to go in PHPUnit
}
