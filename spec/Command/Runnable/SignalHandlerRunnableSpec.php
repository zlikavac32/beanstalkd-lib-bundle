<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Exception;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\InterruptException;
use Zlikavac32\BeanstalkdLib\SignalHandlerInstaller;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\SignalHandlerRunnable;
use Zlikavac32\SymfonyExtras\Command\Runnable\Runnable;

class SignalHandlerRunnableSpec extends ObjectBehavior
{

    public function let(Runnable $runnable, SignalHandlerInstaller $signalHandlerInstaller): void
    {
        $this->beConstructedWith($runnable, $signalHandlerInstaller);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SignalHandlerRunnable::class);
    }

    public function it_should_proxy_configure(Runnable $runnable, InputDefinition $inputDefinition): void
    {
        $runnable->configure($inputDefinition)
            ->shouldBeCalled();

        $this->configure($inputDefinition);
    }

    public function it_should_run_with_signal_handlers_installed(
        Runnable $runnable,
        SignalHandlerInstaller $signalHandlerInstaller,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $runnable->run($input, $output)
            ->willReturn(32);

        $signalHandlerInstaller->install()
            ->shouldBeCalled();
        $signalHandlerInstaller->uninstall()
            ->shouldBeCalled();

        $this->run($input, $output)
            ->shouldReturn(32);
    }

    public function it_should_cleanup_even_on_exception(
        Runnable $runnable,
        SignalHandlerInstaller $signalHandlerInstaller,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $e = new Exception();

        $runnable->run($input, $output)
            ->willThrow($e);

        $signalHandlerInstaller->install()
            ->shouldBeCalled();
        $signalHandlerInstaller->uninstall()
            ->shouldBeCalled();

        $this->shouldThrow($e)
            ->duringRun($input, $output);
    }

    public function it_should_return_0_on_interrupt(
        Runnable $runnable,
        SignalHandlerInstaller $signalHandlerInstaller,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $runnable->run($input, $output)
            ->willThrow(new InterruptException());

        $signalHandlerInstaller->install()
            ->shouldBeCalled();
        $signalHandlerInstaller->uninstall()
            ->shouldBeCalled();

        $this->run($input, $output)->shouldReturn(0);
    }
}
