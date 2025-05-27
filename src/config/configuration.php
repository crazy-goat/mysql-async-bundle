<?php

declare(strict_types=1);

/** @php-cs-fixer-ignore */

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $root = $definition->rootNode();
    \assert($root instanceof ArrayNodeDefinition);

    $root
        ->addDefaultsIfNotSet()
        ->children()
        ->arrayNode('pool')
        ->addDefaultsIfNotSet()
        ->children()
        ->arrayNode('default')
        ->addDefaultsIfNotSet()
        ->children()
        ->scalarNode('url')
        ->info('Connection URL for the default async MySQL pool')
        ->defaultValue('mysql://user:password@host:port/database')
        ->cannotBeEmpty()
        ->end()
        ->end()
        ->end() // default
        ->end()
        ->end() // pool
        ->end()
    ;
};
