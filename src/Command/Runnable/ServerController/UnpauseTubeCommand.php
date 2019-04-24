<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class UnpauseTubeCommand implements Command {

    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void {
        if (!isset($arguments[0])) {
            foreach ($this->client->tubes() as $tube) {
                $tube->pause(0);
            }

            return;
        }

        $tubeName = $arguments[0];

        if (!$this->client->tubes()
            ->hasKey($tubeName)) {
            throw new CommandException(sprintf('Unknown tube %s', $tubeName));
        }

        $this->client->tube($tubeName)
            ->pause(0);
    }

    public function autoComplete(): Sequence {
        $ret = new Vector(['unpause', 'unpause <TUBE-NAME>']);

        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            $ret->push(sprintf('unpause %s', $tubeName));
        }

        return $ret;
    }

    public function help(OutputInterface $output): void {
        $output->writeln(
            <<<'TEXT'
Unpauses tube provided as the first argument. If no tube name
is provided, every tube is unpaused
TEXT
        );
    }

    public function name(): string {
        return 'unpause';
    }
}
