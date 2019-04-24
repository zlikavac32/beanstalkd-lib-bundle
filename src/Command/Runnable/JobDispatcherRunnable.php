<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Ds\Set;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Adapter\Symfony\Console\SymfonyConsoleOutputJobObserver;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobDispatcher;
use Zlikavac32\BeanstalkdLib\Runner\CompositeJobObserver;
use Zlikavac32\SymfonyExtras\Command\Runnable\RunnableWithHelp;

class JobDispatcherRunnable implements RunnableWithHelp {

    /**
     * @var JobDispatcher
     */
    private $jobDispatcher;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var CompositeJobObserver
     */
    private $jobObserver;

    public function __construct(JobDispatcher $jobDispatcher, Client $client, CompositeJobObserver $jobObserver) {
        $this->jobDispatcher = $jobDispatcher;
        $this->client = $client;
        $this->jobObserver = $jobObserver;
    }

    public function configure(InputDefinition $inputDefinition): void {
        $inputDefinition->addOption(
            new InputOption(
                'number-of-jobs',
                'i',
                InputOption::VALUE_OPTIONAL,
                sprintf('Number of jobs to run where value of -1 will use %d as that number', PHP_INT_MAX),
                1
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                'watch',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'List of tubes to watch. By default, all tubes are watched',
                []
            )
        );

        $inputDefinition->addOption(
            new InputOption(
                'exclude',
                'x',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'List of tubes to exclude. By default, no tube is excluded',
                []
            )
        );
    }

    public function run(InputInterface $input, OutputInterface $output): int {
        $watch = $input->getOption('watch');
        $exclude = $input->getOption('exclude');

        if (!empty($watch) && !empty($exclude)) {
            $output->writeln('<error>Only one of watch/exclude can be set</error>');

            return 1;
        }

        $output->writeln('<info>Starting</info>');
        $consoleOutputObserver = new SymfonyConsoleOutputJobObserver($output);

        try {
            $this->jobObserver->append($consoleOutputObserver);

            $numberOfJobs = (int) $input->getOption('number-of-jobs');

            $this->jobDispatcher->run(
                $this->client,
                $this->resolveTubesToRun($watch, $exclude),
                max(
                    1,
                    $numberOfJobs === -1 ? PHP_INT_MAX : $numberOfJobs
                )
            );
        } finally {
            $this->jobObserver->remove($consoleOutputObserver);
        }

        $output->writeln('<info>Exiting</info>');

        return 0;
    }

    private function resolveTubesToRun(array $watch, array $exclude): Set {
        if (empty($watch)) {
            return $this->jobDispatcher->knownTubes()
                ->diff(new Set($exclude));
        }

        return new Set($watch);
    }

    public function help(): string {
        $tubes = $this->jobDispatcher->knownTubes()->sorted()->join("\n");

        return <<<DESC
Runners exist for following tubes:

$tubes
DESC;

    }
}
