<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zlikavac32\BeanstalkdLib\Client;
use Zlikavac32\BeanstalkdLib\JobHandle;
use Zlikavac32\BeanstalkdLib\JobNotFoundException;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\CommandException;
use Zlikavac32\BeanstalkdLibBundle\Command\Runnable\ServerController\DeleteCommand;

class DeleteCommandSpec extends ObjectBehavior
{

    public function let(Client $client): void
    {
        $this->beConstructedWith($client);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(DeleteCommand::class);
    }

    public function it_should_return_autocomplete_list(): void
    {
        $this->autoComplete()
            ->sorted()
            ->toArray()
            ->shouldReturn(['delete <JOB-ID>']);
    }

    public function it_should_write_help_to_output(OutputInterface $output): void
    {
        $output->writeln(Argument::type('string'))
            ->shouldBeCalled();

        $this->help($output);
    }

    public function it_should_have_name_delete(): void
    {
        $this->name()
            ->shouldReturn('delete');
    }

    public function it_should_throw_exception_if_job_id_parameter_is_missing(Client $client, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        $client->peek(Argument::any())->shouldNotBeCalled();

        $this->shouldThrow(new CommandException('Missing <JOB-ID> parameter'))->duringRun([], $input, $helperSet, $output);
    }

    public function it_should_ignore_job_not_found_exception(Client $client, InputInterface $input, HelperSet $helperSet, OutputInterface $output): void
    {
        $client->peek(32)->shouldBeCalled()->willThrow(new JobNotFoundException(32));

        $this->run([32], $input, $helperSet, $output);
    }

    public function it_should_delete_job(Client $client, InputInterface $input, HelperSet $helperSet, OutputInterface $output, JobHandle $jobHandle): void
    {
        $client->peek(32)->willReturn($jobHandle);

        $jobHandle->delete()->shouldBeCalled();

        $this->run([32], $input, $helperSet, $output);

    }
}
