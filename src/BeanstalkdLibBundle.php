<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler\RunnerFactoryPass;
use Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler\TubeConfigurationFactoryPass;
use Zlikavac32\SymfonyExtras\DependencyInjection\Compiler\ConsoleRunnablePass;
use Zlikavac32\SymfonyExtras\DependencyInjection\Compiler\DecoratorPass;
use Zlikavac32\SymfonyExtras\DependencyInjection\Compiler\DynamicCompositePass;
use Zlikavac32\SymfonyExtras\DependencyInjection\Compiler\ServiceLinkerPass;

class BeanstalkdLibBundle extends Bundle {

    public function build(ContainerBuilder $container): void {
        $container->addCompilerPass(new TubeConfigurationFactoryPass());
        $container->addCompilerPass(new RunnerFactoryPass());

        $container->addCompilerPass(new ConsoleRunnablePass());
        $container->addCompilerPass(new DecoratorPass());
        $container->addCompilerPass(new ServiceLinkerPass());
        $container->addCompilerPass(new DynamicCompositePass());
    }
}
