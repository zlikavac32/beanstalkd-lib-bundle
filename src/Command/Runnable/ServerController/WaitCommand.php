<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class WaitCommand implements Command {

    private Client $client;

    private int $sleepTime;

    public function __construct(Client $client, int $sleepTime) {
        $this->client = $client;
        $this->sleepTime = $sleepTime;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        $previous = 0;

        while (($numberOfReservedJobs = $this->client->stats()->serverMetrics()->numberOfReservedJobs()) !== 0) {
            if ($previous !== $numberOfReservedJobs) {
                $previous = $numberOfReservedJobs;
                $output->writeln(sprintf('%d remaining', $numberOfReservedJobs));
            }

            sleep($this->sleepTime);
        }
    }

    public function autoComplete(): Sequence {
        return new Vector(['wait']);
    }

    public function help(OutputInterface $output): void {
        $output->writeln(
            <<<"TEXT"
Waits until there are no reserved jobs in any of the
defined tubes and then finished.

Every {$this->sleepTime} second(s) new stats are pooled.

It can be interrupted with <info>q</info> if run
interactively.
TEXT
        );
    }

    public function name(): string {
        return 'wait';
    }

    public function prototype(): Prototype
    {
        return new Prototype();
    }
}
