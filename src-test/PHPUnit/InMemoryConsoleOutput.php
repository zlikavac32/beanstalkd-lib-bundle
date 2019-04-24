<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\TestHelper\PHPUnit;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Zlikavac32\AlarmScheduler\TestHelper\PHPUnit\UnsupportedMethodCallException;

class InMemoryConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{

    /**
     * @var StreamOutput
     */
    private $errorOutput;
    /**
     * @var ConsoleSectionOutput[]
     */
    private $outputSections = [];

    public function __construct(int $verbosity = self::VERBOSITY_NORMAL)
    {
        parent::__construct($this->openInMemoryStream(), $verbosity, false, null);

        $this->errorOutput = new StreamOutput($this->openInMemoryStream(), $verbosity, false, $this->getFormatter());
    }

    private function openInMemoryStream()
    {
        return fopen('php://memory', 'r+');
    }

    public function section(): ConsoleSectionOutput
    {
        return new ConsoleSectionOutput(
            $this->getStream(),
            $this->outputSections,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter()
        );
    }

    public function setDecorated($decorated): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function setVerbosity($level): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        throw new UnsupportedMethodCallException(__METHOD__);
    }

    public function stdoutContent(): string
    {
        return $this->readStreamContent($this->getStream());
    }

    public function stderrContent(): string
    {
        return $this->readStreamContent($this->errorOutput->getStream());
    }

    private function readStreamContent($stream): string
    {
        $location = ftell($stream);

        fseek($stream, 0, SEEK_SET);

        $content = stream_get_contents($stream);

        fseek($stream, $location, SEEK_SET);

        return $content;
    }
}
