<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Command {

    public function name(): string;

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void;

    public function autoComplete(): Sequence;

    public function help(OutputInterface $output): void;
}
