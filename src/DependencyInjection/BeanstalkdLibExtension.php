<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Zlikavac32\BeanstalkdLib\Socket;
use Zlikavac32\BeanstalkdLib\YamlParser;

class BeanstalkdLibExtension extends Extension {

    public function load(array $configs, ContainerBuilder $container) {
        $yamlLoader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setAlias(Socket::class, $config['adapters']['socket']);
        $container->setAlias(YamlParser::class, $config['adapters']['yaml_parser']);

        $container->setParameter('beanstalkd_lib_host', $config['server']['host']);
        $container->setParameter('beanstalkd_lib_port', $config['server']['port']);

        $yamlLoader->load('parameters.yaml');
        $yamlLoader->load('adapters.yaml');
        $yamlLoader->load('runnable.yaml');
        $yamlLoader->load('decorators.yaml');
        $yamlLoader->load('services.yaml');
    }
}
