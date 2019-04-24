<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\Tests\Integration\DependencyInjection;

use Ds\Set;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zlikavac32\BeanstalkdLib\Adapter\PHP\Socket\NativePHPSocket;
use Zlikavac32\BeanstalkdLib\Adapter\Symfony\Yaml\SymfonyYamlParser;
use Zlikavac32\BeanstalkdLib\Socket;
use Zlikavac32\BeanstalkdLib\YamlParser;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\BeanstalkdLibExtension;

class BeanstalkdLibExtensionTest extends TestCase
{

    /**
     * @var ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var BeanstalkdLibExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->extension = new BeanstalkdLibExtension();
    }

    protected function tearDown(): void
    {
        $this->containerBuilder = null;
        $this->extension = null;
    }

    /**
     * @test
     */
    public function default_host_and_port_parameters_should_be_populated(): void
    {
        $this->extension->load([], $this->containerBuilder);

        self::assertSame('127.0.0.1', $this->containerBuilder->getParameter('beanstalkd_lib_host'));
        self::assertSame(11300, $this->containerBuilder->getParameter('beanstalkd_lib_port'));
    }

    /**
     * @test
     */
    public function configured_host_and_port_parameters_should_be_populated(): void
    {
        $this->extension->load([
            [
                'server'   => [
                    'host' => '16.32.64.128',
                    'port' => 11300
                ],
            ],
        ], $this->containerBuilder);

        self::assertSame('16.32.64.128', $this->containerBuilder->getParameter('beanstalkd_lib_host'));
        self::assertSame(11300, $this->containerBuilder->getParameter('beanstalkd_lib_port'));
    }

    /**
     * @test
     */
    public function default_aliases_for_adapters_are_set(): void
    {
        $this->extension->load([[]], $this->containerBuilder);

        self::assertTrue($this->containerBuilder->hasAlias(Socket::class));
        self::assertSame(NativePHPSocket::class, (string) $this->containerBuilder->getAlias(Socket::class));

        self::assertTrue($this->containerBuilder->hasAlias(YamlParser::class));
        self::assertSame(SymfonyYamlParser::class, (string) $this->containerBuilder->getAlias(YamlParser::class));
    }

    /**
     * @test
     */
    public function configured_default_aliases_for_adapters_are_set(): void
    {
        $this->extension->load([
            [
                'adapters' => [
                    'socket' => 'FooSocket',
                    'yaml_parser' => 'BarYamlParser'
                ]
            ],
        ], $this->containerBuilder);

        self::assertTrue($this->containerBuilder->hasAlias(Socket::class));
        self::assertSame('FooSocket', (string) $this->containerBuilder->getAlias(Socket::class));

        self::assertTrue($this->containerBuilder->hasAlias(YamlParser::class));
        self::assertSame('BarYamlParser', (string) $this->containerBuilder->getAlias(YamlParser::class));
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function configuration_files_are_loaded(): void
    {
        $this->extension->load([[]], $this->containerBuilder);

        $loadedFiles = new Set();

        foreach ($this->containerBuilder->getResources() as $loadedResource) {
            if (!$loadedResource instanceof FileResource) {
                continue;
            }

            $loadedFiles->add($loadedResource->getResource());
        }

        $expectedFiles = ['parameters.yaml', 'adapters.yaml', 'runnable.yaml', 'decorators.yaml', 'services.yaml'];

        foreach ($expectedFiles as $expectedFile) {
            $expectedFileLong = realpath(__DIR__ . '/../../../src/Resources/config/' . $expectedFile);

            if ($loadedFiles->contains($expectedFileLong)) {
                continue;
            }

            $this->fail(sprintf('%s not loaded in extension', $expectedFile));
        }
    }
}
