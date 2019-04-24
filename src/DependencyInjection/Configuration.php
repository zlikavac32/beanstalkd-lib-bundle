<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Zlikavac32\BeanstalkdLib\Adapter\PHP\Socket\NativePHPSocket;
use Zlikavac32\BeanstalkdLib\Adapter\Symfony\Yaml\SymfonyYamlParser;

class Configuration implements ConfigurationInterface {

    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder('beanstalkd_lib');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('adapters')
                    ->children()
                        ->scalarNode('yaml_parser')
                            ->defaultValue(SymfonyYamlParser::class)
                        ->end()
                        ->scalarNode('socket')
                            ->defaultValue(NativePHPSocket::class)
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->arrayNode('server')
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('127.0.0.1')
                        ->end()
                        ->integerNode('port')
                            ->defaultValue(11300)
                        ->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
