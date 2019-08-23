<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Ds\Vector;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Question\Question;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\BeanstalkdServerControllerRunnable;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandRunner;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandRunnerQuitException;
use Zlikavac32\SymfonyExtras\Command\Runnable\RunnableWithHelp;

class BeanstalkdServerControllerRunnableSpec extends ObjectBehavior
{
    public function let(CommandRunner $commandRunner): void
    {
        $this->beConstructedWith($commandRunner);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(BeanstalkdServerControllerRunnable::class);
    }

    public function it_should_configure_input_definition(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addArgument(new InputArgument(
            'cmd',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Command that is run, and then program exists'
        ))
            ->shouldBeCalled();

        $this->configure($inputDefinition);
    }

    public function it_should_run_single_internal_command(CommandRunner $commandRunner, InputInterface $input, ConsoleOutputInterface $output, HelperSet $helperSet): void
    {
        $this->useHelperSet($helperSet);

        $commandRunner->run('help', '', $input, $output, $helperSet)
            ->shouldBeCalled()->willReturn(32);

        $input->getArgument('cmd')
            ->willReturn(['help']);

        $this->run($input, $output)
            ->shouldReturn(32);
    }

    public function it_should_run_single_internal_command_with_escaped_arguments(CommandRunner $commandRunner, InputInterface $input, ConsoleOutputInterface $output, HelperSet $helperSet): void
    {
        $this->useHelperSet($helperSet);

        $commandRunner->run('help', '\'foo bar\'', $input, $output, $helperSet)
            ->shouldBeCalled()->willReturn(32);

        $input->getArgument('cmd')
            ->willReturn(['help', 'foo bar']);

        $this->run($input, $output)
            ->shouldReturn(32);
    }

    public function it_should_fail_when_non_interactive_input_used_without_command(
        InputInterface $input,
        ConsoleOutputInterface $output
    ): void {
        $input->getArgument('cmd')
            ->willReturn([]);

        $input->isInteractive()
            ->willReturn(false);

        $output->writeln('<error>Unable to run in non-interactive mode without command defined</error>')
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(1);
    }

    public function it_should_read_next_line_on_empty_line(
        CommandRunner $commandRunner,
        InputInterface $input,
        ConsoleOutputInterface $output,
        HelperSet $helperSet,
        QuestionHelper $questionHelper
    ): void {

        $input->getArgument('cmd')
            ->willReturn([]);

        $input->isInteractive()
            ->willReturn(true);

        $this->useHelperSet($helperSet);

        $helperSet->get('question')
            ->willReturn($questionHelper);

        $commandRunner->autocomplete()
            ->willReturn(new Vector());

        $questionHelper->ask($input, $output, Argument::type(Question::class))
            ->willReturn(null, 'help foo', 'quit');

        $commandRunner->run('help', 'foo', $input, $output, $helperSet)->shouldBeCalled()
            ->willReturn(0);
        $commandRunner->run('quit', '', $input, $output, $helperSet)->shouldBeCalled()
            ->willThrow(new CommandRunnerQuitException());

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_have_help(): void
    {
        $this->shouldBeAnInstanceOf(RunnableWithHelp::class);
    }
}
