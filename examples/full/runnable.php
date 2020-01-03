<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Examples\Full;

use LogicException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class QueueBruteForceRunnable implements \Zlikavac32\SymfonyExtras\Command\Runnable\Runnable
{

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function configure(InputDefinition $inputDefinition): void
    {

    }

    public function run(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $limit = 10000000;
        $rand = rand(0, $limit - 1);

        $algorithm = BruteForceAlgorithm::values()[array_rand(BruteForceAlgorithm::values())];

        switch ($algorithm) {
            case BruteForceAlgorithm::SHA1():
                $hash = sha1((string)$rand);
                break;
            case BruteForceAlgorithm::MD5():
                $hash = md5((string)$rand);
                break;
            default:
                throw new LogicException();
        }

        $this->client->tube('brute_force_hash')
            ->put(new BruteForceRule($hash, $algorithm, $limit));

        return 0;
    }
}
