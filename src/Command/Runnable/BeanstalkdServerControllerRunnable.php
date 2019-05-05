<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Ds\Map;
use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\Command;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\SymfonyExtras\Command\Runnable\HelperSetAwareRunnable;
use Zlikavac32\SymfonyExtras\Command\Runnable\RunnableWithHelp;

class BeanstalkdServerControllerRunnable implements HelperSetAwareRunnable, RunnableWithHelp
{

    /**
     * @var HelperSet
     */
    private $helperSet;
    /**
     * @var Command[]|Map
     */
    private $commands;

    public function __construct(Command ...$commands)
    {
        $map = new Map();

        foreach ($commands as $command) {
            $map->put($command->name(), $command);
        }

        $this->commands = $map;
    }

    public function configure(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addArgument(new InputArgument('cmd', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Command that is run, and then program exists'));
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        assert($output instanceof ConsoleOutputInterface);

        $dynamicCommands = $this->commands->keys();

        $commandsWithHelpAndQuit = new Vector(['help', 'quit']);

        $commandsWithHelpAndQuit = $commandsWithHelpAndQuit->merge($dynamicCommands)->sorted();

        if (count($input->getArgument('cmd')) > 0) {
            $commandFromInput = $input->getArgument('cmd');

            $nextCommand = array_shift($commandFromInput);

            return $this->runSingleCommand($commandsWithHelpAndQuit, $nextCommand, $commandFromInput, $input, $output);
        }

        if (!$input->isInteractive()) {
            $output->writeln('<error>Unable to run in non-interactive mode without command defined</error>');

            return 1;
        }

        $questionHelper = $this->helperSet->get('question');
        assert($questionHelper instanceof QuestionHelper);

        $staticAutoComplete = new Vector(['help', 'quit']);

        foreach ($dynamicCommands->sorted() as $dynamicCommand) {
            $staticAutoComplete->push(sprintf('help %s', $dynamicCommand));
        }

        while (true) {
            $currentRunAutoComplete = $staticAutoComplete;

            foreach ($this->commands as $command) {
                $currentRunAutoComplete = $currentRunAutoComplete->merge($command->autoComplete());
            }

            $nextCommandQuestion = new Question('> ');
            $nextCommandQuestion->setAutocompleterValues($currentRunAutoComplete->toArray());

            $commandLine = $questionHelper->ask($input, $output, $nextCommandQuestion);

            if (null === $commandLine) {
                continue;
            }

            $parsed = preg_split('/\s+/', trim($commandLine));

            $nextCommand = array_shift($parsed);

            if ('quit' === $nextCommand) {
                break;
            }

            $this->runSingleCommand($commandsWithHelpAndQuit, $nextCommand, $parsed, $input, $output);
        }

        return 0;
    }

    /**
     * @var Sequence|Command[] $commandsWithHelpAndQuit
     */
    private function runSingleCommand(
        Sequence $commandsWithHelpAndQuit,
        string $command,
        array $arguments,
        InputInterface $input,
        OutputInterface $output
    ): int {
        if ('help' === $command) {
            return $this->handleHelpCommand($commandsWithHelpAndQuit, $arguments, $output);
        }

        return $this->runRegisteredCommand($command, $arguments, $input, $output);
    }

    private function runRegisteredCommand(
        string $command,
        array $arguments,
        InputInterface $input,
        OutputInterface $output
    ): int {

        if (!$this->commands->hasKey($command)) {
            $output->writeln(
                sprintf('<error>Unknown command %s. Use help to show available commands</error>', $command)
            );

            return 1;
        }

        try {
            $this->commands[$command]->run($arguments, $input, $this->helperSet, $output);
        } catch (CommandException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }

        return 0;
    }

    private function handleHelpCommand(
        Sequence $commandsWithHelpAndQuit,
        array $arguments,
        OutputInterface $output
    ): int {
        if (!isset($arguments[0])) {
            $this->printHelp($output, $commandsWithHelpAndQuit);

            return 0;
        }
        $commandName = $arguments[0];

        if ('quit' === $commandName) {
            $output->writeln('Exists the program');

            return 0;
        } elseif ('help' === $commandName) {
            $output->writeln('Displays help in general or help for a given command');

            return 0;
        }

        if (!$this->commands->hasKey($commandName)) {
            $output->writeln(
                sprintf('<error>Unknown command %s</error>', $commandName)
            );

            return 1;
        }

        $this->commands[$commandName]->help($output);

        return 0;
    }

    private function printHelp(OutputInterface $output, Sequence $commandsWithHelpAndQuit): void
    {
        $commandsAsList = $commandsWithHelpAndQuit->join("\n");

        $output->writeln(
            <<<HELP
Available commands are:

$commandsAsList
HELP
        );
    }

    public function useHelperSet(HelperSet $helperSet): void
    {
        $this->helperSet = $helperSet;
    }

    public function help(): string
    {
        return <<<'HELP'
Interface towards server administration through client implementation.

It can be run in a command mode with arguments like <info>stats 5</info>
HELP;
    }
}
