<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use Ds\Map;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\TubeHandle;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\ListKnownTubesCommand;

class ListKnownTubesCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ListKnownTubesCommand::class);
    }

    public function it_should_output_list_of_existing_tubes(
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

        $output->writeln('foo')->shouldBeCalled();
        $output->writeln('bar')->shouldBeCalled();

        $this->run([], $input, $helperSet, $output);
    }

    public function it_should_return_autocomplete_list_with_just_list(): void
    {
        $this->autoComplete()->toArray()->shouldReturn(['list']);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_list(): void
    {
        $this->name()->shouldReturn('list');
    }
}
