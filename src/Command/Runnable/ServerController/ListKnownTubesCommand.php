<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class ListKnownTubesCommand implements Command {

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void {
        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            $output->writeln($tubeName);
        }
    }

    public function autoComplete(): Sequence {
        return new Vector(['list']);
    }

    public function help(OutputInterface $output): void {
        $output->writeln('Lists tubes existing on the server');
    }

    public function name(): string {
        return 'list';
    }
}
