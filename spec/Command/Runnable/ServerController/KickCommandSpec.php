<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Map;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\TubeHandle;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\KickCommand;

class KickCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(KickCommand::class);
    }

    public function it_should_return_autocomplete_list(Client $client, TubeHandle $fooTubeHandle): void
    {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
            ]));

        $this->autoComplete()
            ->sorted()
            ->toArray()
            ->shouldReturn([
                'kick <TUBE-NAME>',
                'kick <TUBE-NAME> <NUMBER-OF-JOBS>',
                'kick foo',
                'kick foo <NUMBER-OF-JOBS>',
            ]);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_kick(): void
    {
        $this->name()
            ->shouldReturn('kick');
    }

    public function it_should_have_tube_name_as_required_in_prototype(): void
    {
        $this->prototype()->shouldHaveOperandAsRequired('tube-name');
    }

    public function it_should_kick_one_job_as_default_number_of_jobs(
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output,
        Client $client,
        TubeHandle $tubeHandle
    ): void {
        $client->tube('foo')
            ->willReturn($tubeHandle);

        $tubeHandle->kick(1)
            ->willReturn(1);

        $output->writeln('Kicked 1 job(s)')->shouldBeCalled();

        $this->run(['tube-name' => 'foo', 'number-of-jobs' => null], $input, $helperSet, $output);
    }

    public function it_should_throw_exception_when_number_of_jobs_is_less_than_1(
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $this->shouldThrow(new CommandException('Number of jobs must be >= 1, 0 given'))
            ->duringRun(['tube-name' => 'foo', 'number-of-jobs' => '0'], $input, $helperSet, $output);
    }

    public function it_should_kick_provided_number_of_jobs(
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output,
        Client $client,
        TubeHandle $tubeHandle
    ): void {
        $client->tube('foo')
            ->willReturn($tubeHandle);

        $tubeHandle->kick(32)
            ->willReturn(23);

        $output->writeln('Kicked 23 job(s)')->shouldBeCalled();

        $this->run(['tube-name' => 'foo', 'number-of-jobs' => '32'], $input, $helperSet, $output);
    }
}
