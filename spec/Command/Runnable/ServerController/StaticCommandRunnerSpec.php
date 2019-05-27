<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Vector;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\Command;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\StaticCommandRunner;

class StaticCommandRunnerSpec extends ObjectBehavior
{

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(StaticCommandRunner::class);
    }

    public function it_should_display_help_for_quit(
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $output->writeln('Exists the program')
            ->shouldBeCalled();

        $this->run('help', ['quit'], $input, $output, $helperSet)
            ->shouldReturn(0);
    }

    public function it_should_display_help_for_help(
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $output->writeln('Displays help in general or help for a given command')
            ->shouldBeCalled();

        $this->run('help', ['help'], $input, $output, $helperSet)
            ->shouldReturn(0);
    }

    public function it_should_display_help_for_external_command(
        InputInterface $input,
        ConsoleOutputInterface $output,
        Command $command,
        HelperSet $helperSet
    ): void {
        $this->beConstructedWith($command);

        $command->name()
            ->willReturn('foo');

        $command->help($output)
            ->shouldBeCalled();

        $this->run('help', ['foo'], $input, $output, $helperSet)
            ->shouldReturn(0);
    }

    public function it_should_output_error_and_exit_with_1_when_help_for_command_does_not_exist(
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $output->writeln('<error>Unknown command i-do-not-exist</error>')
            ->shouldBeCalled();

        $this->run('help', ['i-do-not-exist'], $input, $output, $helperSet)
            ->shouldReturn(1);
    }

    public function it_should_fail_when_command_does_not_exist(
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $output->writeln('<error>Unknown command i-do-not-exist. Use help to show available commands</error>')
            ->shouldBeCalled();

        $this->run('i-do-not-exist', [], $input, $output, $helperSet)
            ->shouldReturn(1);
    }

    public function it_should_run_single_command_without_arguments(
        Command $command,
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $command->name()
            ->willReturn('foo');

        $this->beConstructedWith($command);

        $command->run([], $input, $helperSet, $output);

        $this->run('foo', [], $input, $output, $helperSet)
            ->shouldReturn(0);
    }

    public function it_should_run_single_command_with_arguments(
        Command $command,
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet
    ): void {
        $command->name()
            ->willReturn('foo');

        $this->beConstructedWith($command);

        $command->run(['bar', 'baz'], $input, $helperSet, $output);

        $this->run('foo', ['bar', 'baz'], $input, $output, $helperSet)
            ->shouldReturn(0);
    }

    public function it_should_return_autocomplete_list(
        Command $command
    ): void {
        $this->beConstructedWith($command);

        $command->name()
            ->willReturn('foo');

        $command->autoComplete()
            ->willReturn(new Vector(['foo', 'foo bar']));

        $this->autocomplete()
            ->toArray()
            ->shouldReturn(['quit', 'help', 'help quit', 'help foo', 'foo', 'foo bar']);
    }
}
