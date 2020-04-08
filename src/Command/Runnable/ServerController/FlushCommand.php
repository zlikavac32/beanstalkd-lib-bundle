<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Set;
use Ds\Vector;
use GetOpt\Operand;
use GetOpt\Option;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobState;

class FlushCommand implements Command
{

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        $tubeNames = $this->resolveTubeNames($arguments);

        if ($this->shouldAskQuestion($arguments)) {
            $questionHelper = $helperSet->get('question');
            assert($questionHelper instanceof QuestionHelper);


            $shouldFlush = $questionHelper->ask($input, $output, new ConfirmationQuestion(
                sprintf('Flush %d tube(s)? ', count($tubeNames))
            ));

            if (!$shouldFlush) {
                $output->writeln('Flush aborted');

                return ;
            }
        }

        $jobStates = $this->resolveJobStates($arguments);

        foreach ($tubeNames as $tubeName) {
            $this->client->tube($tubeName)->flush($jobStates);
        }
    }

    private function shouldAskQuestion(array $arguments): bool
    {
        return null === $arguments['-f'];
    }

    private function resolveTubeNames(array $arguments): array
    {
        if (null !== $arguments['tube-name']) {
            return [$arguments['tube-name']];
        }

        return $this->client->tubes()->keys()->toArray();
    }

    public function autoComplete(): Sequence
    {
        $ret = new Vector(['flush', 'flush -f', 'flush -b', 'flush -r', 'flush -d', 'flush <TUBE-NAME>', 'flush -f <TUBE-NAME>']);

        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            $ret->push(sprintf('flush %s', $tubeName));
            $ret->push(sprintf('flush -f %s', $tubeName));
        }

        return $ret;
    }

    public function help(OutputInterface $output): void
    {
        $output->writeln(
            <<<'TEXT'
Flushes every tube or a single tube. 

Additional flags to control flushed job states:

- <info>-b</info> - buried
- <info>-d</info> - delayed
- <info>-r</info> - ready

Flags can be combined, and if none is provided, all three are implied.

To skip question, use <info>-f</info> flag.

<comment>Reserved jobs are not flushed.</comment>
TEXT
        );
    }

    public function name(): string
    {
        return 'flush';
    }

    public function prototype(): Prototype
    {
        return new Prototype([
            new Option('f'),
            new Option('b'),
            new Option('d'),
            new Option('r')
        ], [
            new Operand('tube-name')
        ]);
    }

    /**
     * @param string[] $arguments
     *
     * @return Set|JobState[]
     */
    private function resolveJobStates(array $arguments): Set
    {
        $states = new Set();

        foreach (['-r' => JobState::READY(), '-d' => JobState::DELAYED(), '-b' => JobState::BURIED()] as $k => $state) {
            if (isset($arguments[$k])) {
                $states->add($state);
            }
        }

        if ($states->isEmpty()) {
            return new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]);
        }

        return $states;
    }
}
