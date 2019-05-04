<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable;

use Ds\Set;
use PhpSpec\Exception\Example\FailureException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Adapter\Symfony\Console\SymfonyConsoleOutputJobObserver;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobDispatcher;
use Zlikavac32\BeanstalkdLib\Runner\CompositeJobObserver;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\JobDispatcherRunnable;

class JobDispatcherRunnableSpec extends ObjectBehavior
{

    public function let(JobDispatcher $jobDispatcher, Client $client, CompositeJobObserver $jobObserver): void
    {
        $this->beConstructedWith($jobDispatcher, $client, $jobObserver);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(JobDispatcherRunnable::class);
    }

    public function it_should_configure_input_definition(InputDefinition $inputDefinition): void
    {
        $inputDefinition->addOption(
            new InputOption(
                'number-of-jobs',
                'i',
                InputOption::VALUE_OPTIONAL,
                sprintf('Number of jobs to run where value of -1 will use %d as that number', PHP_INT_MAX),
                1
            )
        )
            ->shouldBeCalled();

        $inputDefinition->addOption(
            new InputOption(
                'watch',
                't',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'List of tubes to watch. By default, all tubes are watched',
                []
            )
        )
            ->shouldBeCalled();;

        $inputDefinition->addOption(
            new InputOption(
                'exclude',
                'x',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'List of tubes to exclude. By default, no tube is excluded',
                []
            )
        )
            ->shouldBeCalled();

        $this->configure($inputDefinition);
    }

    public function it_should_print_help(JobDispatcher $jobDispatcher): void
    {
        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['foo', 'bar']));

        $this->help()
            ->shouldReturn('Runners exist for following tubes:

bar
foo');
    }

    public function it_should_exit_with_error_when_both_watch_and_ignore_are_set(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn(['foo']);
        $input->getOption('exclude')
            ->willReturn(['bar']);

        $output->writeln('<error>Only one of watch/exclude can be set</error>')
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    public function it_should_run_all_tubes_when_no_watch_or_exclude_exist(
        JobDispatcher $jobDispatcher,
        Client $client,
        CompositeJobObserver $jobObserver,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn([]);
        $input->getOption('exclude')
            ->willReturn([]);

        $output->writeln('<info>Starting</info>')
            ->shouldBeCalled();

        $output->writeln('<info>Exiting</info>')
            ->shouldBeCalled();

        $observer = new SymfonyConsoleOutputJobObserver($output->getWrappedObject());

        $jobObserver->append($observer)
            ->shouldBeCalled();

        $input->getOption('number-of-jobs')
            ->willReturn(1);

        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['bar', 'foo']));

        $jobDispatcher->run($client, $this->setMatcher(new Set(['foo', 'bar'])), 1)
            ->shouldBeCalled();

        $jobObserver->remove($observer)
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    public function it_should_run_with_watch_tubes_defined(
        JobDispatcher $jobDispatcher,
        Client $client,
        CompositeJobObserver $jobObserver,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn(['foo']);
        $input->getOption('exclude')
            ->willReturn([]);

        $output->writeln('<info>Starting</info>')
            ->shouldBeCalled();

        $output->writeln('<info>Exiting</info>')
            ->shouldBeCalled();

        $observer = new SymfonyConsoleOutputJobObserver($output->getWrappedObject());

        $jobObserver->append($observer)
            ->shouldBeCalled();

        $input->getOption('number-of-jobs')
            ->willReturn(1);

        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['bar', 'foo']));

        $jobDispatcher->run($client, $this->setMatcher(new Set(['foo'])), 1)
            ->shouldBeCalled();

        $jobObserver->remove($observer)
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    public function it_should_run_with_exclude_tubes_defined(
        JobDispatcher $jobDispatcher,
        Client $client,
        CompositeJobObserver $jobObserver,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn([]);
        $input->getOption('exclude')
            ->willReturn(['foo']);

        $output->writeln('<info>Starting</info>')
            ->shouldBeCalled();

        $output->writeln('<info>Exiting</info>')
            ->shouldBeCalled();

        $observer = new SymfonyConsoleOutputJobObserver($output->getWrappedObject());

        $jobObserver->append($observer)
            ->shouldBeCalled();

        $input->getOption('number-of-jobs')
            ->willReturn(1);

        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['bar', 'foo']));

        $jobDispatcher->run($client, $this->setMatcher(new Set(['bar'])), 1)
            ->shouldBeCalled();

        $jobObserver->remove($observer)
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    public function it_should_run_max_number_of_jobs_when_minus_one_is_given(
        JobDispatcher $jobDispatcher,
        Client $client,
        CompositeJobObserver $jobObserver,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn([]);
        $input->getOption('exclude')
            ->willReturn([]);

        $output->writeln('<info>Starting</info>')
            ->shouldBeCalled();

        $output->writeln('<info>Exiting</info>')
            ->shouldBeCalled();

        $observer = new SymfonyConsoleOutputJobObserver($output->getWrappedObject());

        $jobObserver->append($observer)
            ->shouldBeCalled();

        $input->getOption('number-of-jobs')
            ->willReturn(-1);

        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['bar', 'foo']));

        $jobDispatcher->run($client, $this->setMatcher(new Set(['bar'])), PHP_INT_MAX)
            ->shouldBeCalled();

        $jobObserver->remove($observer)
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    public function it_should_run_with_less_than_minus_one_jobs_as_only_one(
        JobDispatcher $jobDispatcher,
        Client $client,
        CompositeJobObserver $jobObserver,
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->getOption('watch')
            ->willReturn([]);
        $input->getOption('exclude')
            ->willReturn([]);

        $output->writeln('<info>Starting</info>')
            ->shouldBeCalled();

        $output->writeln('<info>Exiting</info>')
            ->shouldBeCalled();

        $observer = new SymfonyConsoleOutputJobObserver($output->getWrappedObject());

        $jobObserver->append($observer)
            ->shouldBeCalled();

        $input->getOption('number-of-jobs')
            ->willReturn(-2);

        $jobDispatcher->knownTubes()
            ->willReturn(new Set(['bar', 'foo']));

        $jobDispatcher->run($client, $this->setMatcher(new Set(['bar'])), 1)
            ->shouldBeCalled();

        $jobObserver->remove($observer)
            ->shouldBeCalled();

        $this->run($input, $output);
    }

    private function setMatcher(Set $expectedSet): TokenInterface
    {
        return Argument::that(function ($gotSet) use ($expectedSet): bool {
            if (!$gotSet instanceof Set) {
                throw new FailureException(sprintf('Expected instance of %s', Set::class));
            }

            if ($expectedSet->count() === $expectedSet->count() && $expectedSet->diff($gotSet)
                    ->count() === 0) {
                return true;
            }

            throw new FailureException(
                sprintf(
                    'Expected set [%s] and got set [%s] do not match',
                    $expectedSet->sorted()
                        ->join(', '),
                    $gotSet->sorted()
                        ->join(', ')
                )
            );
        });
    }
}
