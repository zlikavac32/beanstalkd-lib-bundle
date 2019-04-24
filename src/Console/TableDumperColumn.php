<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Console;

use Ds\Hashable;
use Zlikavac32\Enum\Enum;

/**
 * @method static TableDumperColumn TUBE_NAME
 * @method static TableDumperColumn WATCHING
 * @method static TableDumperColumn READY
 * @method static TableDumperColumn RESERVED
 * @method static TableDumperColumn DELAYED
 * @method static TableDumperColumn BURIED
 * @method static TableDumperColumn PAUSED_TIME
 */
abstract class TableDumperColumn extends Enum implements Hashable {

    public function hash(): string {
        return spl_object_hash($this);
    }

    public function equals($obj): bool {
        return $this === $obj;
    }
}
