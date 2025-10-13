<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cantao_solax');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->arrayNode('solax')
                    ->isRequired()
                    ->children()
                        ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('api_version')->defaultValue('v1')->validate()
                            ->ifNotInArray(['v1', 'v2'])
                            ->thenInvalid('Solax API version must be either "v1" or "v2".')
                        ->end()
                        ->scalarNode('api_key')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('serial_number')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('site_id')->defaultNull()->end()
                        ->integerNode('timeout')->defaultValue(10)->min(1)->end()
                        ->integerNode('retry_count')->defaultValue(2)->min(0)->max(10)->end()
                        ->integerNode('retry_delay')->defaultValue(1000)->min(100)->max(60000)->end()
                    ->end()
                ->end()
                ->arrayNode('cantao')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('metric_prefix')->defaultValue('solax')->cannotBeEmpty()->end()
                        ->arrayNode('metric_mapping')
                            ->useAttributeAsKey('source')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->arrayNode('ignore_fields')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->integerNode('decimal_precision')->defaultNull()->min(0)->max(6)->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('table')->defaultValue('tl_solax_metric')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('cron')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('interval')->defaultValue('hourly')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
