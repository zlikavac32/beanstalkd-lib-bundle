<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler;

use Ds\Map;
use LogicException;
use PhpSpec\ObjectBehavior;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler\TubeConfigurationFactoryPass;

class TubeConfigurationFactoryPassSpec extends ObjectBehavior
{

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(TubeConfigurationFactoryPass::class);
    }

    public function it_should_throw_exception_when_tube_key_does_not_exist(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->findTaggedServiceIds('tube_configuration')
            ->willReturn([
                'foo' => [[]],
            ]);

        $this->shouldThrow(new LogicException('Expected tube to be any of [string] in service foo'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_throw_exception_if_two_different_configurations_provide_same_tube(
        ContainerBuilder $containerBuilder
    ): void {
        $containerBuilder->findTaggedServiceIds('tube_configuration')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
                'baz' => [['tube' => 'bar']],
            ]);

        $this->shouldThrow(new LogicException('Tube bar already provided by foo'))
            ->duringProcess($containerBuilder);
    }

    public function it_should_rewire_configuration_factory(
        ContainerBuilder $containerBuilder,
        Definition $bazDefinition,
        Definition $demoDefinition
    ): void {
        $containerBuilder->findTaggedServiceIds('tube_configuration')
            ->willReturn([
                'foo' => [['tube' => 'bar']],
            ]);

        $containerBuilder->setDefinition('tube_configuration.map',
            new Definition(Map::class, [['bar' => new Reference('foo')]]))
            ->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('tube_configuration_map')->willReturn([
            'baz' => [['argument' => '$arg']],
            'demo' => [[]]
        ]);

        $containerBuilder->findDefinition('baz')
            ->willReturn($bazDefinition);
        $containerBuilder->findDefinition('demo')
            ->willReturn($demoDefinition);

        $bazDefinition->setArgument('$arg', new Reference('tube_configuration.map'))
            ->shouldBeCalled();
        $demoDefinition->setArgument(0, new Reference('tube_configuration.map'))
            ->shouldBeCalled();

        $this->process($containerBuilder);
    }

    public function it_should_throw_exception_when_more_than_one_link_tag_exists_on_service(
        ContainerBuilder $containerBuilder
    ): void
    {
        $containerBuilder->findTaggedServiceIds('tube_configuration')
            ->willReturn([]);

        $containerBuilder->setDefinition('tube_configuration.map',
            new Definition(Map::class, [[]]))
            ->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('tube_configuration_map')->willReturn([
            'foo' => [[], []]
        ]);

        $this->shouldThrow(new LogicException('Service foo has multiple tube_configuration_map tags which is not allowed'))->duringProcess($containerBuilder);
    }

    public function it_should_throw_exception_when_argument_on_linker_not_of_valid_type(
        ContainerBuilder $containerBuilder
    ): void
    {
        $containerBuilder->findTaggedServiceIds('tube_configuration')
            ->willReturn([]);

        $containerBuilder->setDefinition('tube_configuration.map',
            new Definition(Map::class, [[]]))
            ->shouldBeCalled();

        $containerBuilder->findTaggedServiceIds('tube_configuration_map')->willReturn([
            'foo' => [['argument' => false]]
        ]);

        $this->shouldThrow(new LogicException('Expected argument to be any of [string, integer] in service foo'))->duringProcess($containerBuilder);
    }
}
