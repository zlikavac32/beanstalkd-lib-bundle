<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Tests\Integration\Console;

use Ds\Map;
use Ds\Set;
use Ds\Vector;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zlikavac32\AlarmScheduler\TestHelper\PHPUnit\UnsupportedMethodCallException;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobHandle;
use Zlikavac32\BeanstalkdLib\ServerStats;
use Zlikavac32\BeanstalkdLib\TubeHandle;
use Zlikavac32\BeanstalkdLib\TubeMetrics;
use Zlikavac32\BeanstalkdLib\TubeStats;
use Zlikavac32\BeanstalkdLibBundle\Console\TableDumperColumn;
use Zlikavac32\BeanstalkdLibBundle\Console\TubeStatsTableDumper;
use Zlikavac32\BeanstalkdLibBundle\TestHelper\PHPUnit\InMemoryConsoleOutput;

class TubeStatsTableDumperTest extends TestCase
{

    private ?DumperMockClient $client;

    protected function setUp(): void
    {
        $this->client = new DumperMockClient(new Map([
            'foo' => new DumperMockTubeHandle(new TubeStats(
                'foo', 0, 0,
                new TubeMetrics(
                    1, 2, 3, 4,
                    5, 546, 3, 44,
                    55, 99, 123
                )
            )),
            'bar' => new DumperMockTubeHandle(new TubeStats(
                'bar', 3600, 3521,
                new TubeMetrics(
                    10, 20, 2, 40,
                    50, 5460, 30, 440,
                    550, 990, 1230
                )
            )),
        ]));
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }

    /**
     * @test
     */
    public function full_table_can_be_rendered(): void
    {
        $output = new InMemoryConsoleOutput();

        (new TubeStatsTableDumper($this->client))->dump($output, new Vector([
            TableDumperColumn::TUBE_NAME(),
            TableDumperColumn::WATCHING(),
            TableDumperColumn::READY(),
            TableDumperColumn::RESERVED(),
            TableDumperColumn::DELAYED(),
            TableDumperColumn::BURIED(),
            TableDumperColumn::PAUSED_TIME(),
        ]));

        $expectedOutput = <<<'TEXT'
+-------------+----------+-------+----------+---------+--------+-----------------+
| < Tube name | Watching | Ready | Reserved | Delayed | Buried | Paused for next |
+-------------+----------+-------+----------+---------+--------+-----------------+
| bar         | 550      | 20    | 2        | 40      | 50     | 58 min 41 s     |
| foo         | 55       | 2     | 3        | 4       | 5      | -/-             |
+-------------+----------+-------+----------+---------+--------+-----------------+

TEXT;

        self::assertSame(
            $expectedOutput,
            $output->stdoutContent()
        );
    }

    /**
     * @test
     */
    public function partial_table_can_be_rendered(): void
    {
        $output = new InMemoryConsoleOutput();

        (new TubeStatsTableDumper($this->client))->dump($output, new Vector([
            TableDumperColumn::TUBE_NAME(),
            TableDumperColumn::READY(),
            TableDumperColumn::RESERVED(),
        ]));

        $expectedOutput = <<<'TEXT'
+-------------+-------+----------+
| < Tube name | Ready | Reserved |
+-------------+-------+----------+
| bar         | 20    | 2        |
| foo         | 2     | 3        |
+-------------+-------+----------+

TEXT;

        self::assertSame(
            $expectedOutput,
            $output->stdoutContent()
        );
    }

    /**
     * @test
     */
    public function different_column_can_be_used_for_sort(): void
    {
        $output = new InMemoryConsoleOutput();

        (new TubeStatsTableDumper($this->client))->dump($output, new Vector([
            TableDumperColumn::TUBE_NAME(),
            TableDumperColumn::READY(),
            TableDumperColumn::RESERVED(),
        ]), 1);

        $expectedOutput = <<<'TEXT'
+-----------+---------+----------+
| Tube name | < Ready | Reserved |
+-----------+---------+----------+
| foo       | 2       | 3        |
| bar       | 20      | 2        |
+-----------+---------+----------+

TEXT;

        self::assertSame(
            $expectedOutput,
            $output->stdoutContent()
        );
    }

    /**
     * @test
     */
    public function sort_order_can_be_changed(): void
    {
        $output = new InMemoryConsoleOutput();

        (new TubeStatsTableDumper($this->client))->dump($output, new Vector([
            TableDumperColumn::TUBE_NAME(),
            TableDumperColumn::READY(),
            TableDumperColumn::RESERVED(),
        ]), 0, true);

        $expectedOutput = <<<'TEXT'
+-------------+-------+----------+
| > Tube name | Ready | Reserved |
+-------------+-------+----------+
| foo         | 2     | 3        |
| bar         | 20    | 2        |
+-------------+-------+----------+

TEXT;

        self::assertSame(
            $expectedOutput,
            $output->stdoutContent()
        );
    }
}

/**
 * @internal
 */
class DumperMockClient implements Client
{

    /**
     * @var TubeHandle[]|Map
     */
    private $tubes;

    public function __construct(Map $tubes)
    {
        $this->tubes = $tubes;
    }

    public function tubes(): Map
    {
        return $this->tubes;
    }

    public function tube(string $tubeName): TubeHandle
    {
        throw new LogicException();
    }

    public function stats(): ServerStats
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function reserve(): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function peek(int $jobId): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function reserveWithTimeout(int $timeout): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function watch(string $tubeName): int
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function ignoreDefaultTube(): int
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function ignore(string $tubeName): int
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function watchedTubeNames(): Set
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function flush(Set $states): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }
}

/**
 * @internal
 */
class DumperMockTubeHandle implements TubeHandle
{

    /**
     * @var TubeStats
     */
    private $tubeStats;

    public function __construct(TubeStats $tubeStats)
    {
        $this->tubeStats = $tubeStats;
    }

    public function tubeName(): string
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function kick(int $numberOfJobs): int
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function put($payload, ?int $priority = null, ?int $delay = null, ?int $timeToRun = null): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function stats(): TubeStats
    {
        return $this->tubeStats;
    }

    public function pause(?int $delay = null): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function peekReady(): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function peekDelayed(): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function peekBuried(): JobHandle
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function flush(Set $states): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }
}
