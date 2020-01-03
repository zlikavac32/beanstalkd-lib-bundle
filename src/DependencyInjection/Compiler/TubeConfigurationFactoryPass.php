<?php

declare(strict_types=1);

namespace Zlikavac32\BeanstalkdLibBundle\DependencyInjection\Compiler;

use Ds\Map;
use Ds\Set;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use function Zlikavac32\SymfonyExtras\DependencyInjection\assertOnlyOneTagPerService;
use function Zlikavac32\SymfonyExtras\DependencyInjection\assertValueIsOfType;

class TubeConfigurationFactoryPass implements CompilerPassInterface {

    private string $tag;

    private string $linkerTag;

    public function __construct(
        string $tag = 'tube_configuration',
        string $linkerTag = 'tube_configuration_map'
    ) {
        $this->tag = $tag;
        $this->linkerTag = $linkerTag;
    }

    public function process(ContainerBuilder $container): void {
        /** @var Map|Reference[] $map */
        $map = new Map();

        foreach ($container->findTaggedServiceIds($this->tag) as $serviceId => $tags) {
            $this->collectConfigurationsFromTags($serviceId, $tags, $map);
        }

        $mapServiceId = $this->tag . '.map';

        $container->setDefinition($mapServiceId, new Definition(Map::class, [$map->toArray()]));

        foreach ($container->findTaggedServiceIds($this->linkerTag) as $serviceId => $tags) {
            assertOnlyOneTagPerService($tags, $this->linkerTag, $serviceId);

            $tag = $tags[0];

            $argument = $tag['argument'] ?? 0;

            assertValueIsOfType($argument, new Set(['string', 'integer']), 'argument', $serviceId);

            $container->findDefinition($serviceId)
                ->setArgument($argument, new Reference($mapServiceId));
        }
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

                throw new LogicException(
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
}
