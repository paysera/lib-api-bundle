<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Persistence\ObjectRepository;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\DoctrinePathAttributeResolver;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

class PayseraApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/services'));
        class_exists(AttributeRouteControllerLoader::class)
            ? $loader->load('attributes.xml')
            : $loader->load('annotations.xml');

        $container->setParameter('paysera_api.locales', $config['locales']);
        if (count($config['locales']) === 0) {
            $container->removeDefinition('paysera_api.listener.locale');
        }

        if ($config['validation']['property_path_converter'] !== null) {
            $container->setAlias(
                'paysera_api.validation.property_path_converter',
                $config['validation']['property_path_converter']
            );
        }

        if (count($config['path_attribute_resolvers']) > 0 && !class_exists('Doctrine\ORM\EntityManager')) {
            throw new RuntimeException(
                'Please install doctrine/orm before configuring paysera_api.path_attribute_resolvers'
            );
        }

        foreach ($config['path_attribute_resolvers'] as $className => $resolverConfig) {
            $container->setDefinition(
                'paysera_api.auto_registered.path_attribute_resolver.' . $className,
                $this->buildPathAttributeResolverDefinition($className, $resolverConfig['field'])
            );
        }

        $this->configurePagination($container, $config['pagination']);
        $this->overrideDummyAnnotationRegistry($loader);
    }

    private function buildPathAttributeResolverDefinition(string $className, string $field): Definition
    {
        $repositoryDefinition = (new Definition(ObjectRepository::class, [$className]))
            ->setFactory([new Reference('doctrine.orm.entity_manager'), 'getRepository'])
        ;

        return (new Definition(DoctrinePathAttributeResolver::class, [
            $repositoryDefinition,
            $field,
        ]))->addTag('paysera_api.path_attribute_resolver', ['type' => $className]);
    }

    private function configurePagination(ContainerBuilder $container, array $paginationConfig)
    {
        $container->setParameter(
            'paysera_api.pagination.default_total_count_strategy',
            $paginationConfig['total_count_strategy']
        );
        $container->setParameter(
            'paysera_api.pagination.maximum_offset',
            $paginationConfig['maximum_offset']
        );
        $container->setParameter(
            'paysera_api.pagination.default_limit',
            $paginationConfig['default_limit']
        );
        $container->setParameter(
            'paysera_api.pagination.maximum_limit',
            $paginationConfig['maximum_limit']
        );
    }

    private function overrideDummyAnnotationRegistry(Loader\XmlFileLoader $loader): void
    {
        if (Kernel::VERSION_ID < 40000 || Kernel::VERSION >= 50400) {
            return;
        }

        // override the dummy registry when doctrine/annotations v2 is used
        if (
            !method_exists(AnnotationRegistry::class, 'registerLoader')
            || !method_exists(AnnotationRegistry::class, 'registerUniqueLoader')
        ) {
            $loader->load('annotation_registry.xml');
        }
    }
}
