<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Map;
use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StaticCommandRunner implements CommandRunner
{

    /**
     * @var Command[]|Map
     */
    private $commands;
    /**
     * @var ArgumentsProcessor
     */
    private $argumentsProcessor;

    public function __construct(ArgumentsProcessor $argumentsProcessor, Command ...$commands)
    {
        $map = new Map();

        foreach ($commands as $command) {
            $map->put($command->name(), $command);
        }

        $this->commands = $map;
        $this->argumentsProcessor = $argumentsProcessor;
    }

    public function run(
        string $commandName,
        string $arguments,
        InputInterface $input,
        OutputInterface $output,
        HelperSet $helperSet
    ): int {
        if ('help' === $commandName) {
            return $this->handleHelpCommand(
                (new Vector(['help', 'quit']))
                    ->merge($this->commands->keys())
                    ->sorted(),
                $arguments,
                $output
            );
        } elseif ('quit' === $commandName) {
            throw new CommandRunnerQuitException();
        }

        return $this->runRegisteredCommand($commandName, $arguments, $input, $output, $helperSet);
    }

    private function runRegisteredCommand(
        string $command,
        string $arguments,
        InputInterface $input,
        OutputInterface $output,
        HelperSet $helperSet
    ): int {

        if (!$this->commands->hasKey($command)) {
            $output->writeln(
                sprintf('<error>Unknown command %s. Use help to show available commands</error>', $command)
            );

            return 1;
        }

        try {
            $command = $this->commands[$command];

            $command->run(
                $this->argumentsProcessor->process($command->prototype(), $arguments),
                $input,
                $helperSet,
                $output
            );
        } catch (CommandException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return 1;
        }

        return 0;
    }

    private function handleHelpCommand(
        Sequence $commandsWithHelpAndQuit,
        string $commandName,
        OutputInterface $output
    ): int {
        if (empty($commandName)) {
            $this->printHelp($output, $commandsWithHelpAndQuit);

            return 0;
        }

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

    /**
     * @return Sequence|string[]
     */
    public function autocomplete(): Sequence
    {

        $autoComplete = new Vector(['quit', 'help', 'help quit']);

        foreach ($this->commands->keys()
                     ->sorted() as $dynamicCommand) {
            $autoComplete->push(sprintf('help %s', $dynamicCommand));
        }

        foreach ($this->commands as $command) {
            $autoComplete = $autoComplete->merge($command->autoComplete());
        }

        return $autoComplete;
    }
}
