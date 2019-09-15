<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('paysera_rest');

        $children = $rootNode->children();
        $children->arrayNode('locales')->defaultValue([])->prototype('scalar');

        /** @var ArrayNodeDefinition $findDenormalizersNode */
        $findDenormalizersNode = $children
            ->arrayNode('find_denormalizers')
            ->defaultValue([])
            ->useAttributeAsKey('class')
            ->prototype('array')
        ;
        $denormalizerPrototype = $findDenormalizersNode->children();
        $denormalizerPrototype->scalarNode('field')->defaultValue('id');

        $validationNode = $children->arrayNode('validation')->addDefaultsIfNotSet()->children();
        $validationNode->scalarNode('property_path_converter')->defaultNull();

        return $treeBuilder;
    }
}
