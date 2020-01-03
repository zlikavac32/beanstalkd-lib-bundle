<?php

declare(strict_types=1);

use Zlikavac32\NSBDecorators\Proxy;

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(Proxy::class.'::loadFQN');

error_reporting(E_ALL);
