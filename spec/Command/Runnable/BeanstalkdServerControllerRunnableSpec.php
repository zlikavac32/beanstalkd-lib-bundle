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
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\Command;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\SymfonyExtras\Command\Runnable\RunnableWithHelp;

class BeanstalkdServerControllerRunnableSpec extends ObjectBehavior
{

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

    public function it_should_run_single_internal_command(InputInterface $input, ConsoleOutputInterface $output): void
    {
        $input->getArgument('cmd')
            ->willReturn(['help']);

        $output->writeln(<<<'TEXT'
Available commands are:

help
quit
TEXT
        )
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_display_help_for_quit(InputInterface $input, ConsoleOutputInterface $output): void
    {
        $input->getArgument('cmd')
            ->willReturn(['help', 'quit']);

        $output->writeln('Exists the program')
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_display_help_for_help(InputInterface $input, ConsoleOutputInterface $output): void
    {
        $input->getArgument('cmd')
            ->willReturn(['help', 'help']);

        $output->writeln('Displays help in general or help for a given command')
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_display_help_for_external_command(
        InputInterface $input,
        ConsoleOutputInterface $output,
        Command $command
    ): void {
        $this->beConstructedWith($command);

        $command->name()
            ->willReturn('foo');
        $command->autoComplete()
            ->willReturn(new Vector());

        $input->getArgument('cmd')
            ->willReturn(['help', 'foo']);

        $command->help($output)
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_output_error_and_exit_with_1_when_help_for_command_does_not_exist(
        InputInterface $input,
        ConsoleOutputInterface $output
    ): void {
        $input->getArgument('cmd')
            ->willReturn(['help', 'i-do-not-exist']);

        $output->writeln('<error>Unknown command i-do-not-exist</error>')
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(1);
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

        $question = new Question('> ');
        $question->setAutocompleterValues(['help', 'quit']);

        $questionHelper->ask($input, $output, $question)
            ->willReturn(null, 'help', 'quit');

        $output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_run_few_command_and_then_exit(
        InputInterface $input,
        ConsoleOutputInterface $output,
        Command $fooCommand,
        Command $barCommand,
        Command $bazCommand,
        HelperSet $helperSet,
        QuestionHelper $questionHelper
    ): void {
        $this->beConstructedWith($fooCommand, $barCommand, $bazCommand);

        $fooCommand->name()
            ->willReturn('foo');
        $barCommand->name()
            ->willReturn('bar');
        $bazCommand->name()
            ->willReturn('baz');

        $fooCommand->autoComplete()
            ->willReturn(new Vector(['foo']));
        $barCommand->autoComplete()
            ->willReturn(new Vector(['bar']));
        $bazCommand->autoComplete()
            ->willReturn(new Vector(['baz']));

        $input->getArgument('cmd')
            ->willReturn([]);

        $input->isInteractive()
            ->willReturn(true);

        $this->useHelperSet($helperSet);

        $helperSet->get('question')
            ->willReturn($questionHelper);

        $question = new Question('> ');
        $question->setAutocompleterValues(['help', 'quit', 'help bar', 'help baz', 'help foo', 'foo', 'bar', 'baz']);

        $questionHelper->ask($input, $output, $question)
            ->willReturn('foo 123', 'help', 'baz', 'bar demo', 'bar', 'quit');

        $fooCommand->run(['123'], $input, $helperSet, $output)
            ->shouldBeCalled();
        $barCommand->run(['demo'], $input, $helperSet, $output)
            ->shouldBeCalled();
        $barCommand->run([], $input, $helperSet, $output)
            ->shouldBeCalled();
        $bazCommand->run([], $input, $helperSet, $output)
            ->willThrow(new CommandException('Baz exception'));

        $output->writeln('<error>Baz exception</error>')->shouldBeCalled();

        $output->writeln(<<<'TEXT'
Available commands are:

bar
baz
foo
help
quit
TEXT
        )
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(0);
    }

    public function it_should_have_help(): void
    {
        $this->shouldBeAnInstanceOf(RunnableWithHelp::class);
    }
}
