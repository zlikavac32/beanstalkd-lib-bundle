<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Sequence;
use Ds\Vector;
use GetOpt\Operand;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;

class PauseTubeCommand implements Command {

    private Client $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function run(array $arguments, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void {
        $tubeName = $arguments['tube-name'];
        $sleepTime = $arguments['pause-time'];

        if (null === $tubeName && null === $sleepTime) {
            $this->pauseAll();

            return ;
        }

        if (null !== $tubeName && null === $sleepTime) {
            try {
                $this->pauseAll($this->parseSleepTime($tubeName));

                return ;
            } catch (CommandException $e) {
                // ignore
            }
        }

        $defaultPauseTime = null;

        if (null !== $sleepTime) {
            $defaultPauseTime = $this->parseSleepTime($sleepTime);
        }

        if (!$this->client->tubes()
            ->hasKey($tubeName)) {
            throw new CommandException(sprintf('Unknown tube %s', $tubeName));
        }

        $this->client->tube($tubeName)
            ->pause($defaultPauseTime);
    }

    private function pauseAll(?int $sleepTime = null): void {
        foreach ($this->client->tubes() as $tube) {
            $tube->pause($sleepTime);
        }
    }

    private function parseSleepTime(string $pauseTime): int {
        if (preg_match('/^(?=0*[1-9])\d+$/', $pauseTime)) {
            return (int) $pauseTime;
        }

        if (
            '' !== $pauseTime
            &&
            preg_match('/^(?:(?<h>\d+)h)?(?:(?<m>\d+)m)?(?:(?<s>\d+)s)?$/', $pauseTime, $matches)
        ) {
            $time = 0;

            foreach (['h' => 3600, 'm' => 60, 's' => 1] as $unit => $multiplier) {
                if (!isset($matches[$unit]) || empty($matches[$unit])) {
                    continue;
                }

                $time += $multiplier * $matches[$unit];
            }

            if ($time > 0) {
                return $time;
            }
        }

        throw new CommandException(
            sprintf(
                'Pause time must be a positive integer or in format XhYmZs for X hours, Y minutes and Z seconds, but %s was given',
                $pauseTime
            )
        );
    }

    public function autoComplete(): Sequence {
        $ret = new Vector(['pause', 'pause <PAUSE-TIME>', 'pause <TUBE-NAME>', 'pause <TUBE-NAME> <PAUSE-TIME>']);

        $tubeNames = $this->client
            ->tubes()
            ->keys();

        foreach ($tubeNames as $tubeName) {
            $ret->push(sprintf('pause %s', $tubeName));
            $ret->push(sprintf('pause %s <PAUSE-TIME>', $tubeName));
        }

        return $ret;
    }

    public function help(OutputInterface $output): void {
        $output->writeln(
            <<<'TEXT'
Pauses tube provided as the first argument. If no argument is provided,
every tube is paused with it's default pause time.

If no additional argument is provided, then tube default pause time is used.

If second argument is provided, it must be either a positive integer that
represents number of seconds to pause for, or it can be string in format
<info>XhYmZs</info>. <info>X</info> is number of hours, <info>Y</info> is number of minutes and <info>Z</info> is number of 
seconds to pause for. Parts that are zero can be left out.

pause <info>3h20m</info> pauses for 3 hours and 20 minutes
TEXT
        );
    }

    public function name(): string {
        return 'pause';
    }

    public function prototype(): Prototype
    {
        return new Prototype([], [
            new Operand('tube-name'),
            new Operand('pause-time'),
        ]);
    }
}
