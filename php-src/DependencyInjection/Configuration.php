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
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('base_url')->defaultValue('https://www.solaxcloud.com:9443')->cannotBeEmpty()->end()
                        ->scalarNode('api_version')->defaultValue('v1')->validate()
                            ->ifNotInArray(['v1', 'v2'])
                            ->thenInvalid('Solax API version must be either "v1" or "v2".')
                        ->end()
                        ->scalarNode('api_key')->defaultNull()->end()
                        ->scalarNode('serial_number')->defaultNull()->end()
                        ->scalarNode('site_id')->defaultNull()->end()
                        ->integerNode('timeout')->defaultValue(10)->min(1)->end()
                        ->integerNode('retry_count')->defaultValue(2)->min(0)->max(10)->end()
                        ->integerNode('retry_delay')->defaultValue(1000)->min(100)->max(60000)->end()
                    ->end()
                ->end()
                ->arrayNode('fake_data')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->floatNode('latitude')->defaultValue(52.52)->min(-90)->max(90)->end()
                        ->floatNode('longitude')->defaultValue(13.405)->min(-180)->max(180)->end()
                        ->floatNode('peak_power')->defaultValue(5000.0)->min(0)->end()
                        ->floatNode('base_total_yield')->defaultValue(2500.0)->min(0)->end()
                        ->floatNode('cloud_variability')->defaultValue(0.35)->min(0)->max(1)->end()
                        ->floatNode('household_base_load')->defaultValue(600.0)->min(0)->end()
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
