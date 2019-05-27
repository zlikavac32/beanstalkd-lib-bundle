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
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\PauseTubeCommand;

class PauseTubeCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(PauseTubeCommand::class);
    }

    public function it_should_return_autocomplete_list_with_just_list(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $this->autoComplete()
            ->sorted()
            ->toArray()
            ->shouldReturn([
                'pause',
                'pause <PAUSE-TIME>',
                'pause <TUBE-NAME>',
                'pause <TUBE-NAME> <PAUSE-TIME>',
                'pause bar',
                'pause bar <PAUSE-TIME>',
                'pause foo',
                'pause foo <PAUSE-TIME>',
            ]);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_list(): void
    {
        $this->name()
            ->shouldReturn('pause');
    }

    public function it_should_pause_all_tubes_without_arguments(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $fooTubeHandle->pause(null)
            ->shouldBeCalled();
        $barTubeHandle->pause(null)
            ->shouldBeCalled();

        $this->run(['tube-name' => null, 'pause-time' => null], $input, $helperSet, $output);
    }

    public function it_should_pause_all_tubes_without_timeout_when_valid_string_timeout_is_first_argument(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $fooTubeHandle->pause(2 * 3600 + 10 * 60 + 20)
            ->shouldBeCalled();
        $barTubeHandle->pause(2 * 3600 + 10 * 60 + 20)
            ->shouldBeCalled();

        $this->run(['tube-name' => '2h10m20s', 'pause-time' => null], $input, $helperSet, $output);
    }

    public function it_should_pause_all_tubes_without_timeout_when_valid_seconds_timeout_is_first_argument(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $fooTubeHandle->pause(32)
            ->shouldBeCalled();
        $barTubeHandle->pause(32)
            ->shouldBeCalled();

        $this->run(['tube-name' => '32', 'pause-time' => null], $input, $helperSet, $output);
    }

    public function it_should_pause_only_tube_mentioned_in_argument(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $client->tube('foo')
            ->willReturn($fooTubeHandle);

        $fooTubeHandle->pause(null)
            ->shouldBeCalled();
        $barTubeHandle->pause(Argument::any())
            ->shouldNotBeCalled();

        $this->run(['tube-name' => 'foo', 'pause-time' => null], $input, $helperSet, $output);
    }

    public function it_should_pause_only_tube_mentioned_in_argument_with_timeout(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $client->tube('foo')
            ->willReturn($fooTubeHandle);

        $fooTubeHandle->pause(32)
            ->shouldBeCalled();
        $barTubeHandle->pause(Argument::any())
            ->shouldNotBeCalled();

        $this->run(['tube-name' => 'foo', 'pause-time' => '32'], $input, $helperSet, $output);
    }

    public function it_should_throw_exception_when_tube_name_in_argument_does_not_exist(
        Client $client,
        TubeHandle $fooTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
            ]));

        $this->shouldThrow(new CommandException('Unknown tube does-not-exist'))
            ->duringRun(['tube-name' => 'does-not-exist', 'pause-time' => null], $input, $helperSet, $output);
    }

    public function it_should_throw_exception_when_timeout_is_not_correct(
        Client $client,
        TubeHandle $fooTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
            ]));

        $this->shouldThrow(new CommandException('Pause time must be a positive integer or in format XhYmZs for X hours, Y minutes and Z seconds, but bar was given'))
            ->duringRun(['tube-name' => 'foo', 'pause-time' => 'bar'], $input, $helperSet, $output);
    }

    public function it_should_throw_exception_when_timeout_is_zero(
        Client $client,
        TubeHandle $fooTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
            ]));

        $this->shouldThrow(new CommandException('Pause time must be a positive integer or in format XhYmZs for X hours, Y minutes and Z seconds, but 0 was given'))
            ->duringRun(['tube-name' => 'foo', 'pause-time' => '0'], $input, $helperSet, $output);
    }
}
