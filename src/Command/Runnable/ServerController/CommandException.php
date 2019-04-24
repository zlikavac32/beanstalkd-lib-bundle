<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use RuntimeException;
use Throwable;

class CommandException extends RuntimeException {
    public function __construct(string $message, Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}
