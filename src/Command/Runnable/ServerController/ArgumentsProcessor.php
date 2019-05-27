<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

interface ArgumentsProcessor
{

    /**
     * @throws CommandException
     */
    public function process(Prototype $prototype, string $line): array;
}
