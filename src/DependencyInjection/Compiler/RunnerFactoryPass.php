<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler;

use Ds\Map;
use Ds\Set;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function Zlikavac32\SymfonyExtras\DependencyInjection\assertOnlyOneTagPerService;
use function Zlikavac32\SymfonyExtras\DependencyInjection\assertValueIsOfType;

class RunnerFactoryPass implements CompilerPassInterface {

    private string $runnerTag;

    private string $linkRunnerTag;

    private string $linkTubesTag;

    public function __construct(
        string $runnerTag = 'job_runner',
        string $linkRunnerTag = 'job_dispatcher.runners',
        string $linkTubesTag = 'job_dispatcher.tubes'
    ) {
        $this->runnerTag = $runnerTag;
        $this->linkRunnerTag = $linkRunnerTag;
        $this->linkTubesTag = $linkTubesTag;
    }

    public function process(ContainerBuilder $container): void {
        /** @var Map|Reference[] $map */
        $map = new Map();

        foreach ($container->findTaggedServiceIds($this->runnerTag) as $serviceId => $tags) {
            $this->collectConfigurationsFromTags($serviceId, $tags, $map);
        }

        $runnersServiceId = $this->linkRunnerTag;
        $tubesServiceId = $this->linkTubesTag;

        $container->setDefinition($runnersServiceId, new Definition(Map::class, [$map->toArray()]));
        $container->setDefinition(
            $tubesServiceId,
            new Definition(
                Set::class,
                [
                    $map->keys()
                        ->toArray(),
                ]
            )
        );

        $this->linkForTag($container, $this->linkRunnerTag, $runnersServiceId);
        $this->linkForTag($container, $this->linkTubesTag, $tubesServiceId);
    }

    private function collectConfigurationsFromTags(
        string $serviceId,
        array $tags,
        Map $map
    ): void {
        foreach ($tags as $tag) {
            $tubeName = $tag['tube'] ?? null;

            assertValueIsOfType($tubeName, new Set(['string']), 'tube', $serviceId);

            if ($map->hasKey($tubeName)) {
                $reference = $map->get($tubeName);
                assert($reference instanceof Reference);

                throw new \LogicException(
                    sprintf(
                        'Tube %s already provided by %s',
                        $tubeName,
                        $reference
                    )
                );
            }

            $map->put($tubeName, new Reference($serviceId));
        }
    }

    private function linkForTag(ContainerBuilder $container, string $tagName, string $linkedServiceId): void
    {

        foreach ($container->findTaggedServiceIds($tagName) as $serviceId => $tags) {
            assertOnlyOneTagPerService($tags, $tagName, $serviceId);

            $argument = $tags[0]['argument'] ?? 0;

            assertValueIsOfType($argument, new Set(['string', 'integer']), 'argument', $serviceId);

            $container->findDefinition($serviceId)
                ->setArgument($argument, new Reference($linkedServiceId));
        }
    }
}
