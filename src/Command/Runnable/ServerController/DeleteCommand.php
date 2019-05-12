<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobNotFoundException;

class DeleteCommand implements Command
{

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        if (!isset($arguments[0])) {
            throw new CommandException('Missing <JOB-ID> parameter');
        }

        try {
            $this->client->peek((int)$arguments[0])
                ->delete();
        } catch (JobNotFoundException $e) {
            // job deleted, ignore
        }
    }

    public function autoComplete(): Sequence
    {
        return new Vector(['delete <JOB-ID>']);
    }

    public function help(OutputInterface $output): void
    {
        $output->writeln(
            <<<'TEXT'
Deletes job with id <info><JOB-ID></info>.
TEXT
        );
    }

    public function name(): string
    {
        return 'delete';
    }
}
