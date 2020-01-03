<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Examples\Full;

use Ds\Hashable;
use Zlikavac32\BeanstalkdLib\DeserializeException;
use Zlikavac32\BeanstalkdLib\Serializer;
use Zlikavac32\Enum\Enum;

/**
 * @method static BruteForceAlgorithm MD5
 * @method static BruteForceAlgorithm SHA1
 */
abstract class BruteForceAlgorithm extends Enum implements Hashable
{

    public function hash(): string
    {
        return spl_object_hash($this);
    }

    public function equals($obj): bool
    {
        return $obj === $this;
    }
}

// Domain object
class BruteForceRule
{

    private BruteForceAlgorithm $algorithm;

    private int $range;

    private string $hash;

    public function __construct(string $hash, BruteForceAlgorithm $algorithm, int $range)
    {
        $this->algorithm = $algorithm;
        $this->range = $range;
        $this->hash = $hash;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function algorithm(): BruteForceAlgorithm
    {
        return $this->algorithm;
    }

    public function range(): int
    {
        return $this->range;
    }
}

// Serializer for our domain object
class BruteForceSerializer implements Serializer
{

    public function serialize($payload): string
    {
        assert($payload instanceof BruteForceRule);

        return sprintf('%s|%s|%d', $payload->hash(), $payload->algorithm()
            ->name(), $payload->range());
    }

    public function deserialize(string $payload)
    {
        $parts = explode('|', $payload);

        if (count($parts) !== 3) {
            throw new DeserializeException('Expected payload in format hash|alogithm|range', $payload);
        }

        return new BruteForceRule(
            $parts[0],
            BruteForceAlgorithm::valueOf($parts[1]),
            (int)$parts[2]
        );
    }
}

