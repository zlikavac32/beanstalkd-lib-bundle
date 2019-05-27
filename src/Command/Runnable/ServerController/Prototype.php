<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use GetOpt\Operand;
use GetOpt\Option;

class Prototype
{

    /**
     * @var array|Option[]
     */
    private $options;
    /**
     * @var array|Operand[]
     */
    private $operands;

    public function __construct(array $options = [], array $operands = [])
    {
        $this->options = $options;
        $this->operands = $operands;
    }

    /**
     * @return array|Option[]
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @return array|Operand[]
     */
    public function operands(): array
    {
        return $this->operands;
    }
}
