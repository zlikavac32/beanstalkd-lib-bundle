<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use GetOpt\Operand;
use GetOpt\Option;
use LogicException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobHandle;
use Zlikavac32\BeanstalkdLib\NotFoundException;
use function Zlikavac32\BeanstalkdLib\microTimeToHuman;

class PeekCommand implements Command
{

    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        try {
            $jobHandle = $this->peekJobHandle($arguments);
        } catch (NotFoundException $e) {
            throw new CommandException($e->getMessage(), $e);
        }

        if ($this->shouldUsePager($input, $output)) {
            assert($output instanceof StreamOutput);
            assert($output instanceof ConsoleOutputInterface);

            $errorOutput = $output->getErrorOutput();
            assert($errorOutput instanceof StreamOutput);

            $pipes = [
                0 => ['pipe', 'r'],
                1 => $output->getStream(),
                2 => $errorOutput->getStream(),
            ];

            $process = proc_open($_SERVER['PAGER'], $pipes, $pipes);

            if (false === $process) {
                throw new CommandException(sprintf('Unable to open pager %s', $_SERVER['PAGER']));
            }

            $this->dumpJobHandleInfo(
                $jobHandle,
                new StreamOutput($pipes[0], $output->getVerbosity(), $output->isDecorated(), $output->getFormatter())
            );

            fclose($pipes[0]);

            $exit = proc_close($process);

            if (0 !== $exit) {
                throw new CommandException('Pager not closed properly. Messed up output is possible');
            }

            return;
        }

        $this->dumpJobHandleInfo($jobHandle, $output);
    }

    private function peekJobHandle(array $arguments): JobHandle
    {
        $options = $arguments['-r'] + $arguments['-b'] + $arguments['-d'];

        if (0 === $options) {
            return $this->client->peek((int) $arguments['tube-name-or-job-id']);
        } else if ($options > 1) {
            throw new CommandException('Only one of -d, -b and -r can be used at a time');
        }

        $tube = $this->client->tube($arguments['tube-name-or-job-id']);

        switch (true) {
            case $arguments['-r']:
                return $tube->peekReady();

                break;
            case $arguments['-b']:
                return $tube->peekBuried();

                break;
            case $arguments['-d']:
                return $tube->peekDelayed();

                break;
            default:
                throw new LogicException();
        }
    }

    private function shouldUsePager(InputInterface $input, OutputInterface $output): bool
    {
        return !empty($_SERVER['PAGER'])
            &&
            $output instanceof StreamOutput
            &&
            $output instanceof ConsoleOutputInterface
            &&
            $output->getErrorOutput() instanceof StreamOutput
            &&
            $input instanceof StreamableInputInterface
            &&
            posix_isatty($input->getStream());
    }

    private function dumpJobHandleInfo(JobHandle $jobHandle, OutputInterface $output): void
    {
        $output->writeln(sprintf('<info>ID:</info> %d', $jobHandle->id()));

        $jobStats = $jobHandle->stats();

        $output->writeln(sprintf('<info>Delay:</info> %s', microTimeToHuman((float)$jobStats->delay()) ?: '-/-'));
        $output->writeln(sprintf('<info>Priority:</info> %d', $jobStats->priority()));
        $output->writeln(sprintf('<info>Time-to-run:</info> %s', microTimeToHuman($jobStats->timeToRun())));
        $output->writeln(sprintf('<info>Time left:</info> %s', microTimeToHuman($jobStats->timeLeft()) ?: '-/-'));
        $output->writeln(sprintf('<info>Age:</info> %s', microTimeToHuman((float)$jobStats->age())));

        $jobMetrics = $jobStats->metrics();

        $output->writeln(sprintf('<info>Buries:</info> %d', $jobMetrics->numberOfBuries()));
        $output->writeln(sprintf('<info>Kicks:</info> %d', $jobMetrics->numberOfKicks()));
        $output->writeln(sprintf('<info>Releases:</info> %d', $jobMetrics->numberOfReleases()));
        $output->writeln(sprintf('<info>Reserves:</info> %d', $jobMetrics->numberOfReserves()));
        $output->writeln(sprintf('<info>Timeouts:</info> %d', $jobMetrics->numberOfTimeouts()));

        $output->writeln('');
        $output->writeln('<info>Payload:</info>');

        // @todo: I'd really like to extract this as services
        $cloner = new VarCloner();
        $dumper = new CliDumper();

        $dumper->setColors($output->isDecorated());

        if ($output instanceof StreamOutput) {
            $dumper->setOutput($output->getStream());
        }

        $dumper->dump($cloner->cloneVar($jobHandle->payload()));
    }

    public function autoComplete(): Sequence
    {
        $ret = new Vector(['peek <JOB-ID>', 'peek <JOB-STATE> <TUBE-NAME>']);

        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            foreach (['-r', '-d', '-b'] as $jobState) {
                $ret->push(sprintf('peek %s %s', $jobState, $tubeName));
            }
        }

        return $ret;
    }

    public function help(OutputInterface $output): void
    {
        $output->writeln(
            <<<'TEXT'
Peeks ready/delayed/buried job in a tube or by ID.

<info>-b</info> - buried jobs
<info>-d</info> - delayed jobs
<info>-r</info> - ready jobs

If <info>$PAGER</info> environment variable exists, it's used to page
the output if possible.
TEXT
        );
    }

    public function name(): string
    {
        return 'peek';
    }

    public function prototype(): Prototype
    {
        return new Prototype([
            new Option('b'),
            new Option('d'),
            new Option('r'),
        ], [
            new Operand('tube-name-or-job-id', Operand::REQUIRED)
        ]);
    }
}
