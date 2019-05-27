<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandRunner;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandRunnerQuitException;
use Zlikavac32\SymfonyExtras\Command\Runnable\HelperSetAwareRunnable;
use Zlikavac32\SymfonyExtras\Command\Runnable\RunnableWithHelp;

class BeanstalkdServerControllerRunnable implements HelperSetAwareRunnable, RunnableWithHelp
{

    /**
     * @var HelperSet
     */
    private $helperSet;
    /**
     * @var CommandRunner
     */
    private $commandRunner;

    public function __construct(CommandRunner $commandRunner)
    {
        $this->commandRunner = $commandRunner;
    }

    public function configure(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addArgument(new InputArgument('cmd', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Command that is run, and then program exists'));
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        assert($output instanceof ConsoleOutputInterface);

        if (count($input->getArgument('cmd')) > 0) {
            $commandFromInput = $input->getArgument('cmd');

            $nextCommand = array_shift($commandFromInput);

            try {
                return $this->commandRunner->run($nextCommand,
                    implode(
                        ' ',
                        array_map('escapeshellarg', $commandFromInput)
                    ),
                    $input,
                    $output,
                    $this->helperSet
                );
            } catch (CommandRunnerQuitException $e) {
                return 0;
            }
        }

        if (!$input->isInteractive()) {
            $output->writeln('<error>Unable to run in non-interactive mode without command defined</error>');

            return 1;
        }

        $questionHelper = $this->helperSet->get('question');
        assert($questionHelper instanceof QuestionHelper);

        while (true) {
            $nextCommandQuestion = new Question('> ');
            $nextCommandQuestion->setAutocompleterValues($this->commandRunner->autocomplete()->toArray());

            $commandLine = $questionHelper->ask($input, $output, $nextCommandQuestion);

            if (null === $commandLine) {
                continue;
            }

            $parsed = preg_split('/\s+/', trim($commandLine), 2);

            $nextCommand = $parsed[0];

            try {
                $this->commandRunner->run($nextCommand, $parsed[1] ?? '', $input, $output, $this->helperSet);
            } catch (CommandRunnerQuitException $e) {
                break;
            }
        }

        return 0;
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
