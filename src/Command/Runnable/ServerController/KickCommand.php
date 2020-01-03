<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use GetOpt\Operand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class KickCommand implements Command {

    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void {
        $tubeName = $arguments['tube-name'];
        $numberOfJobs = 1;

        if (null !== $arguments['number-of-jobs']) {
            $numberOfJobs = (int) $arguments['number-of-jobs'];

            if ($numberOfJobs < 1) {
                throw new CommandException(sprintf('Number of jobs must be >= 1, %d given', $numberOfJobs));
            }
        }

        $jobsKicked = $this->client->tube($tubeName)
            ->kick($numberOfJobs);

        $output->writeln(sprintf('Kicked %d job(s)', $jobsKicked));
    }

    public function autoComplete(): Sequence {
        $ret = new Vector(['kick <TUBE-NAME>', 'kick <TUBE-NAME> <NUMBER-OF-JOBS>']);

        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            $ret->push(sprintf('kick %s', $tubeName));
            $ret->push(sprintf('kick %s <NUMBER-OF-JOBS>', $tubeName));
        }

        return $ret;
    }

    public function help(OutputInterface $output): void {
        $output->writeln(
            <<<'TEXT'
Kicks 1 job in the given tube.

If <info><NUMBER-OF-JOBS></info> is provided,
that at most that number of jobs is kicked.

Commands prints number of kicked jobs.
TEXT
        );
    }

    public function name(): string {
        return 'kick';
    }

    public function prototype(): Prototype
    {
        return new Prototype([], [
            new Operand('tube-name', Operand::REQUIRED),
            new Operand('number-of-jobs')
        ]);
    }
}
