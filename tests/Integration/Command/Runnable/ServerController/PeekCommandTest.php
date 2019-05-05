<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Tests\Integration\Console;

use Ds\Map;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobHandle;
use Zlikavac32\BeanstalkdLib\JobMetrics;
use Zlikavac32\BeanstalkdLib\JobNotFoundException;
use Zlikavac32\BeanstalkdLib\JobState;
use Zlikavac32\BeanstalkdLib\JobStats;
use Zlikavac32\BeanstalkdLib\NotFoundException;
use Zlikavac32\BeanstalkdLib\TubeHandle;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\PeekCommand;
use Zlikavac32\BeanstalkdLibBundle\TestHelper\PHPUnit\InMemoryConsoleOutput;

class PeekCommandTest extends TestCase
{

    /**
     * @var InMemoryConsoleOutput
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var Client|MockObject
     */
    private $client;
    /**
     * @var TubeHandle|MockObject
     */
    private $tubeHandle;
    /**
     * @var JobHandle|MockObject
     */
    private $jobHandle;
    /**
     * @var PeekCommand
     */
    private $command;

    protected function setUp(): void
    {
        $this->output = new InMemoryConsoleOutput();
        $this->input = $this->createMock(InputInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->tubeHandle = $this->createMock(TubeHandle::class);
        $this->jobHandle = $this->createMock(JobHandle::class);
        $this->command = new PeekCommand($this->client);
    }

    protected function tearDown(): void
    {
        $this->output = null;
        $this->input = null;
        $this->client = null;
        $this->tubeHandle = null;
        $this->jobHandle = null;
    }

    /**
     * @test
     */
    public function name_is_peek(): void
    {
        self::assertSame('peek', $this->command->name());
    }

    /**
     * @test
     */
    public function help_is_generated(): void
    {
        $this->command->help($this->output);

        self::assertGreaterThan(1, strlen($this->output->stdoutContent()));
    }

    /**
     * @test
     */
    public function autocomplete_list_is_generated(): void
    {
        $this->client->method('tubes')
            ->willReturn(new Map([
                'foo' => $this->tubeHandle,
            ]));

        self::assertSame([
            'peek <JOB-ID>',
            'peek <JOB-STATE> <TUBE-NAME>',
            'peek -r foo',
            'peek -d foo',
            'peek -b foo',
        ], $this->command->autoComplete()
            ->toArray());
    }

    /**
     * @test
     */
    public function unknown_job_id_throws_exception(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Job 32 not found on the server');

        $this->client->method('peek')
            ->with($this->equalTo(32))
            ->willThrowException(new JobNotFoundException(32));

        $this->command->run(['32'], $this->input, new HelperSet(), $this->output);
    }

    /**
     * @test
     */
    public function unknown_tube_throws_exception(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('No job in tube');

        $this->client->method('tube')
            ->with($this->equalTo('foo'))
            ->willReturn($this->tubeHandle);

        $this->tubeHandle->method('peekReady')
            ->willThrowException(new NotFoundException('No job in tube'));

        $this->command->run(['-r', 'foo'], $this->input, new HelperSet(), $this->output);
    }

    /**
     * @test
     */
    public function unknown_job_state_throws_exception(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unknown state abc given');

        $this->client->method('tube')
            ->with($this->equalTo('foo'))
            ->willReturn($this->tubeHandle);

        $this->command->run(['abc', 'foo'], $this->input, new HelperSet(), $this->output);
    }

    /**
     * @test
     */
    public function output_is_generated_for_ready_state_with_non_pager_output(): void
    {
        $this->client->method('tube')
            ->with($this->equalTo('foo'))
            ->willReturn($this->tubeHandle);

        $this->tubeHandle->method('peekReady')
            ->willReturn($this->jobHandle);

        $this->jobHandle->method('id')
            ->willReturn(32);
        $this->jobHandle->method('payload')
            ->willReturn(['foo' => 'bar']);
        $this->jobHandle->method('stats')
            ->willReturn(new JobStats(
                32, 'foo', JobState::READY(), 1024, 693,
                0, 300, 0,
                new JobMetrics(4, 6, 8, 9, 19)
            ));

        $this->command->run(['-r', 'foo'], $this->input, new HelperSet(), $this->output);

        $expectedOutput = <<<'TEXT'
ID: 32
Delay: -/-
Priority: 1024
Time-to-run: 5 min
Time left: -/-
Age: 11 min 33 s
Buries: 9
Kicks: 19
Releases: 8
Reserves: 4
Timeouts: 6

Payload:
array:1 [
  "foo" => "bar"
]

TEXT;

        self::assertSame($expectedOutput, $this->output->stdoutContent());
    }
}
