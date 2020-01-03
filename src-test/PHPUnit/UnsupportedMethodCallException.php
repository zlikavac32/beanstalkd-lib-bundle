<?php

declare(strict_types=1);

namespace Zlikavac32\AlarmScheduler\TestHelper\PHPUnit;

use LogicException;
use Throwable;

class UnsupportedMethodCallException extends LogicException
{

    private string $classAndMethod;

    public function __construct(string $classAndMethod, int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('%s not expected to be called', $classAndMethod), $code, $previous);
        $this->classAndMethod = $classAndMethod;
    }

    public function classAndMethod(): string
    {
        return $this->classAndMethod;
    }
}
