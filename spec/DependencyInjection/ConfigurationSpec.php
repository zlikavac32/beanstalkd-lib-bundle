<?php

declare(strict_types=1);

namespace spec\Zlikavac32\BeanstalkdLibBundle\DependencyInjection;

use PhpSpec\ObjectBehavior;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Configuration;

class ConfigurationSpec extends ObjectBehavior
{

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Configuration::class);
    }

    public function it_should_build_default_configuration(): void
    {
        $tree = $this->getConfigTreeBuilder()
            ->buildTree();

        $tree->finalize($tree->normalize([]))
            ->shouldReturn([
                'adapters' => [
                    'yaml_parser' => 'Zlikavac32\BeanstalkdLib\Adapter\Symfony\Yaml\SymfonyYamlParser',
                    'socket'      => 'Zlikavac32\BeanstalkdLib\Adapter\PHP\Socket\NativePHPSocket',
                ],
                'server'   => [
                    'host' => "127.0.0.1",
                    'port' => 11300,
                ],
            ]);
    }
}
