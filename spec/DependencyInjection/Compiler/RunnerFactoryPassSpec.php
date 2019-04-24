<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler;

use Ds\Map;
use Ds\Set;
use LogicException;
use PhpSpec\ObjectBehavior;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler\RunnerFactoryPass;

class RunnerFactoryPassSpec extends ObjectBehavior
{

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(RunnerFactoryPass::class);
    }

    public function it_should_throw_exception_when_tube_key_does_not_exist(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [[]],
            ]);

        $this->shouldThrow(new LogicException('Expected tube to be any of [string] in service foo'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_throw_exception_if_two_different_runners_support_same_tube(
        ContainerBuilder $containerBuilder
    ): void {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
                'baz' => [['tube' => 'bar']],
            ]);

        $this->shouldThrow(new LogicException('Tube bar already provided by foo'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_throw_exception_if_linker_referenced_more_than_once_on_a_service(
        ContainerBuilder $containerBuilder
    ): void {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
            ]);

        $containerBuilder->setDefinition('job_dispatcher.runners',
            new Definition(Map::class, [['bar' => new Reference('foo')]]))->shouldBeCalled();
        $containerBuilder->setDefinition('job_dispatcher.tubes',
            new Definition(Set::class, [['bar']]))->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('job_dispatcher.runners')->willReturn([
            'service' => [[], []]
        ]);

        $this->shouldThrow(new LogicException('Service service has multiple job_dispatcher.runners tags which is not allowed'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_throw_exception_if_argument_property_is_not_string_or_int(
        ContainerBuilder $containerBuilder
    ): void {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
            ]);

        $containerBuilder->setDefinition('job_dispatcher.runners',
            new Definition(Map::class, [['bar' => new Reference('foo')]]))->shouldBeCalled();
        $containerBuilder->setDefinition('job_dispatcher.tubes',
            new Definition(Set::class, [['bar']]))->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('job_dispatcher.runners')->willReturn([
            'service' => [['argument' => 1.2]]
        ]);

        $this->shouldThrow(new LogicException('Expected argument to be any of [string, integer] in service service'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_link_runner(
        ContainerBuilder $containerBuilder,
        Definition $serviceDefinition
    ): void {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
            ]);

        $containerBuilder->setDefinition('job_dispatcher.runners',
            new Definition(Map::class, [['bar' => new Reference('foo')]]))->shouldBeCalled();
        $containerBuilder->setDefinition('job_dispatcher.tubes',
            new Definition(Set::class, [['bar']]))->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('job_dispatcher.runners')->willReturn([
            'service' => [[]]
        ]);

        $containerBuilder->findTaggedServiceIds('job_dispatcher.tubes')->willReturn([]);

        $containerBuilder->findDefinition('service')->willReturn($serviceDefinition);

        $serviceDefinition->setArgument(0, new Reference('job_dispatcher.runners'))->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_should_link_tubes(
        ContainerBuilder $containerBuilder,
        Definition $serviceDefinition
    ): void {
        $containerBuilder->findTaggedServiceIds('job_runner')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
            ]);

        $containerBuilder->setDefinition('job_dispatcher.runners',
            new Definition(Map::class, [['bar' => new Reference('foo')]]))->shouldBeCalled();
        $containerBuilder->setDefinition('job_dispatcher.tubes',
            new Definition(Set::class, [['bar']]))->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('job_dispatcher.tubes')->willReturn([
            'service' => [[]]
        ]);

        $containerBuilder->findTaggedServiceIds('job_dispatcher.runners')->willReturn([]);

        $containerBuilder->findDefinition('service')->willReturn($serviceDefinition);

        $serviceDefinition->setArgument(0, new Reference('job_dispatcher.tubes'))->shouldBeCalled();

        $this->process($containerBuilder);
    }
}
