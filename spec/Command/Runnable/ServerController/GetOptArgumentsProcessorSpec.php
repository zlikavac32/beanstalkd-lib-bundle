<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use GetOpt\ArgumentException;
use GetOpt\Operand;
use GetOpt\Option;
use PhpSpec\ObjectBehavior;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\Prototype;

class GetOptArgumentsProcessorSpec extends ObjectBehavior
{

    public function it_should_process_single_short_option(): void
    {
        $this->process(new Prototype([new Option('f')]), '-f')
            ->shouldReturn(['-f' => 1]);
    }

    public function it_should_process_single_long_option(): void
    {
        $this->process(new Prototype([new Option(null, 'foo')]), '--foo')
            ->shouldReturn(['--foo' => 1]);
    }

    public function it_should_process_single_long_and_short_option(): void
    {
        $this->process(new Prototype([new Option('f', 'foo')]), '--foo')
            ->shouldReturn(['--foo' => 1, '-f' => 1]);

        $this->process(new Prototype([new Option('f', 'foo')]), '--f')
            ->shouldReturn(['--foo' => 1, '-f' => 1]);
    }

    public function it_should_process_operand(): void
    {
        $this->process(new Prototype([], [new Operand('foo')]), 'some-text')
            ->shouldReturn(['foo' => 'some-text']);
    }

    public function it_should_process_optional_option_as_null(): void
    {
        $this->process(new Prototype([new Option('f', 'foo')]), '')
            ->shouldReturn(['--foo' => null, '-f' => null]);
    }

    public function it_should_process_optional_operand_as_null(): void
    {
        $this->process(new Prototype([], [new Operand('foo')]), '')
            ->shouldReturn(['foo' => null]);
    }

    public function it_should_throw_command_exception_on_processing_error(): void
    {
        $this->shouldThrow(new CommandException('Option \'f\' is unknown', new ArgumentException()))
            ->duringProcess(new Prototype([]), '-f');
    }
}
