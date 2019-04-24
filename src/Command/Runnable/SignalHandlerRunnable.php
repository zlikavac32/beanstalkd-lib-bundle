<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\InterruptException;
use Zlikavac32\BeanstalkdLib\SignalHandlerInstaller;
use Zlikavac32\SymfonyExtras\Command\Runnable\Runnable;

class SignalHandlerRunnable implements Runnable {

    /**
     * @var Runnable
     */
    private $runnable;
    /**
     * @var SignalHandlerInstaller
     */
    private $signalHandlerInstaller;

    public function __construct(Runnable $runnable, SignalHandlerInstaller $signalHandlerInstaller) {
        $this->runnable = $runnable;
        $this->signalHandlerInstaller = $signalHandlerInstaller;
    }

    public function configure(InputDefinition $inputDefinition): void {
        $this->runnable->configure($inputDefinition);
    }

    public function run(InputInterface $input, OutputInterface $output): int {
        $this->signalHandlerInstaller->install();

        try {
            return $this->runnable->run($input, $output);
        } catch (InterruptException $e) {
            return 0;
        } finally {
            $this->signalHandlerInstaller->uninstall();
        }
    }
}
