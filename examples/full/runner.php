<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Examples\Full;

use Zlikavac32\BeanstalkdLib\JobHandle;
use Zlikavac32\BeanstalkdLib\Runner;

class BruteForceIntegerHashRunner implements Runner
{

    public function run(JobHandle $jobHandle): void
    {
        $rule = $jobHandle->payload();
        assert($rule instanceof BruteForceRule);

        $this->performSearch($rule);

        $jobHandle->delete();
    }

    private function performSearch(BruteForceRule $rule): void
    {
        $expected = $rule->hash();

        for ($i = 0, $limit = $rule->range(); $i < $limit; $i++) {
            switch ($rule->algorithm()) {
                case BruteForceAlgorithm::MD5():
                    $val = md5((string)$i);
                    break;
                case BruteForceAlgorithm::SHA1():
                    $val = sha1((string)$i);
                    break;
                default:
                    throw new LogicException();
            }

            if ($val === $expected) {
                printf('Found %s(%d) === %s', (string)$rule->algorithm(), $i, $expected);
                echo "\n";

                return;
            }
        }

        printf('No %s(?) === %s found', (string)$rule->algorithm(), $expected);
        echo "\n";
    }
}
