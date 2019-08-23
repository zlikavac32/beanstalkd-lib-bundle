<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Map;
use Ds\Set;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Argument\Token\TokenInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobState;
use Zlikavac32\BeanstalkdLib\TubeHandle;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\FlushCommand;

class FlushCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FlushCommand::class);
    }

    public function it_should_return_autocomplete_list_with_just_list(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $this->autoComplete()
            ->sorted()
            ->toArray()
            ->shouldReturn([
                'flush',
                'flush -b',
                'flush -d',
                'flush -f',
                'flush -f <TUBE-NAME>',
                'flush -f bar',
                'flush -f foo',
                'flush -r',
                'flush <TUBE-NAME>',
                'flush bar',
                'flush foo',
            ]);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_list(): void
    {
        $this->name()
            ->shouldReturn('flush');
    }

    public function it_should_flush_tube_on_force(
        Client $client,
        TubeHandle $tubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tube('foo')->willReturn($tubeHandle);

        $tubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();

        $this->run(['-f' => 1, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => 'foo'], $input, $helperSet, $output);
    }

    public function it_should_flush_all_tubes_on_force(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $client->tube('foo')->willReturn($fooTubeHandle);
        $client->tube('bar')->willReturn($barTubeHandle);

        $fooTubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();
        $barTubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();

        $this->run(['-f' => 1, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => null], $input, $helperSet, $output);
    }

    public function it_should_flush_tube_without_force_when_confirmed(
        Client $client,
        TubeHandle $tubeHandle,
        InputInterface $input,
        QuestionHelper $questionHelper,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $helperSet->get('question')
            ->willReturn($questionHelper);

        $questionHelper->ask($input, $output, $this->confirmQuestionToken(1))
            ->willReturn(true);

        $client->tube('foo')->willReturn($tubeHandle);

        $tubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();

        $this->run(['-f' => null, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => 'foo'], $input, $helperSet, $output);
    }

    public function it_should_flush_all_tubes_without_force_when_confirmed(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        QuestionHelper $questionHelper,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $client->tube('foo')->willReturn($fooTubeHandle);
        $client->tube('bar')->willReturn($barTubeHandle);

        $helperSet->get('question')
            ->willReturn($questionHelper);

        $questionHelper->ask($input, $output, $this->confirmQuestionToken(2))
            ->willReturn(true);

        $fooTubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();
        $barTubeHandle->flush(new Set([JobState::READY(), JobState::BURIED(), JobState::DELAYED()]))->shouldBeCalled();

        $this->run(['-f' => null, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => null], $input, $helperSet, $output);
    }

    public function it_should_not_flush_tube_without_force_when_not_confirmed(
        Client $client,
        InputInterface $input,
        QuestionHelper $questionHelper,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $helperSet->get('question')
            ->willReturn($questionHelper);

        $questionHelper->ask($input, $output, $this->confirmQuestionToken(1))
            ->willReturn(false);

        $client->tube(Argument::any())->shouldNotBeCalled();

        $this->run(['-f' => null, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => 'foo'], $input, $helperSet, $output);
    }

    public function it_should_not_flush_all_tubes_without_force_when_not_confirmed(
        Client $client,
        TubeHandle $fooTubeHandle,
        TubeHandle $barTubeHandle,
        InputInterface $input,
        QuestionHelper $questionHelper,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
            ->willReturn(new Map([
                'foo' => $fooTubeHandle->getWrappedObject(),
                'bar' => $barTubeHandle->getWrappedObject(),
            ]));

        $helperSet->get('question')
            ->willReturn($questionHelper);

        $questionHelper->ask($input, $output, $this->confirmQuestionToken(2))
            ->willReturn(false);


        $client->tube(Argument::any())->shouldNotBeCalled();

        $this->run(['-f' => null, '-r' => null, '-d' => null, '-b' => null, 'tube-name' => null], $input, $helperSet, $output);
    }

    public function it_should_flush_only_listed_states(
        Client $client,
        TubeHandle $tubeHandle,
        InputInterface $input,
        HelperSet $helperSet,
        OutputInterface $output
    ): void {
        $client->tubes()
               ->willReturn(new Map([
                   'foo' => $tubeHandle->getWrappedObject(),
               ]));

        $client->tube('foo')->willReturn($tubeHandle);

        $tubeHandle->flush(new Set([JobState::DELAYED(), JobState::BURIED()]))->shouldBeCalled();

        $this->run(['-f' => 1, '-r' => null, '-d' => 1, '-b' => 1, 'tube-name' => null], $input, $helperSet, $output);
    }

    private function confirmQuestionToken(int $count): TokenInterface
    {
        return Argument::that(function ($data) use ($count): bool {
            return $data instanceof ConfirmationQuestion && $data->getQuestion() === sprintf('Flush %d tube(s)? ', $count);
        });
    }
}
