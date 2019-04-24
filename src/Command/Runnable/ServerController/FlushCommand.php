<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\Protocol;
use Zlikavac32\BeanstalkdLib\ProtocolTubePurger;

class FlushCommand implements Command
{

    /**
     * @var Client
     */
    private $client;
    /**
     * @var Protocol
     */
    private $protocol;
    /**
     * @var ProtocolTubePurger
     */
    private $protocolTubePurger;

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

        foreach ($tubeNames as $tubeName) {
            $this->client->tube($tubeName)->flush();
        }
    }

    private function shouldAskQuestion(array $arguments): bool
    {
        return !(isset($arguments[0]) && '-f' === $arguments[0]);
    }

    private function resolveTubeNames(array $arguments): array
    {
        if (isset($arguments[1])) {
            return [$arguments[1]];
        }

        if (isset($arguments[0]) && '-f' !== $arguments[0]) {
            return [$arguments[0]];
        }

        return $this->client->tubes()->keys()->toArray();
    }

    public function autoComplete(): Sequence
    {
        $ret = new Vector(['flush', 'flush -f', 'flush <TUBE-NAME>', 'flush -f <TUBE-NAME>']);

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

To skip question, use -f flag.

Reserved jobs are not flushed.
TEXT
        );
    }

    public function name(): string
    {
        return 'flush';
    }
}
