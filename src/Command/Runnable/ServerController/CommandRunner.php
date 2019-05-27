<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface CommandRunner
{

    /**
     * @throws CommandRunnerQuitException
     */
    public function run(string $commandName, array $arguments, InputInterface $input, OutputInterface $outputm , HelperSet $helperSet): int;

    /**
     * @return Sequence|string[]
     */
    public function autocomplete(): Sequence;
}
