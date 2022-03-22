<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\DependencyInjection;

use Paysera\Bundle\ApiBundle\Entity\PagedQuery;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('paysera_api');
        $rootNode = method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('paysera_api')
        ;

        $children = $rootNode->children();
        $children->arrayNode('locales')->defaultValue([])->prototype('scalar');

        /** @var ArrayNodeDefinition $findDenormalizersNode */
        $findDenormalizersNode = $children
            ->arrayNode('path_attribute_resolvers')
            ->defaultValue([])
            ->useAttributeAsKey('class')
            ->prototype('array')
        ;
        $denormalizerPrototype = $findDenormalizersNode->children();
        $denormalizerPrototype->scalarNode('field')->defaultValue('id');

        $validationNode = $children->arrayNode('validation')->addDefaultsIfNotSet()->children();
        $validationNode->scalarNode('property_path_converter')->defaultNull();

        $this->configurePagination($children->arrayNode('pagination'));

        return $treeBuilder;
    }

    private function configurePagination(ArrayNodeDefinition $paginationArrayNode)
    {
        $paginationNode = $paginationArrayNode->addDefaultsIfNotSet()->children();
        $availableStrategies = [
            PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
            PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
            PagedQuery::TOTAL_COUNT_STRATEGY_NEVER,
        ];
        $strategyNode = $paginationNode
            ->scalarNode('total_count_strategy')
            ->defaultValue(PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL)
        ;
        $strategyNode->validate()
            ->ifNotInArray($availableStrategies)
            ->thenInvalid(sprintf(
                'must be one of %s',
                implode(', ', $availableStrategies)
            ))
        ;

        $paginationNode->scalarNode('maximum_offset')->defaultValue(1000);
        $paginationNode
            ->scalarNode('maximum_limit')
            ->defaultValue(1000)
            ->validate()
            ->ifTrue(function ($value) {
                return !is_int($value) || $value <= 0;
            })
            ->thenInvalid('must be positive integer')
        ;
        $paginationNode->scalarNode('default_limit')->defaultValue(100);

        $paginationArrayNode->validate()
            ->ifTrue(function ($value) {
                return $value['default_limit'] > $value['maximum_limit'];
            })
            ->thenInvalid('default_limit cannot be greater than maximum_limit')
        ;
    }
}
