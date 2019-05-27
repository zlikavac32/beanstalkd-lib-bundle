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
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\UnpauseTubeCommand;

class UnpauseTubeCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(UnpauseTubeCommand::class);
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
            ->shouldReturn(['unpause', 'unpause <TUBE-NAME>', 'unpause bar', 'unpause foo']);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_list(): void
    {
        $this->name()
            ->shouldReturn('unpause');
    }

    public function it_should_unpause_all_tubes_without_arguments(
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

        $fooTubeHandle->pause(0)
            ->shouldBeCalled();
        $barTubeHandle->pause(0)
            ->shouldBeCalled();

        $this->run(['tube-name' => null], $input, $helperSet, $output);
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
            ->duringRun(['tube-name' => 'does-not-exist'], $input, $helperSet, $output);
    }

    public function it_should_unpause_only_tube_mentioned_in_argument(
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

        $fooTubeHandle->pause(0)
            ->shouldBeCalled();
        $barTubeHandle->pause(Argument::any())
            ->shouldNotBeCalled();

        $this->run(['tube-name' => 'foo'], $input, $helperSet, $output);
    }
}
