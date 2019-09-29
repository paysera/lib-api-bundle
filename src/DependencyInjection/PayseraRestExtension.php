<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\DependencyInjection;

use Doctrine\Common\Persistence\ObjectRepository;
use Paysera\Bundle\RestBundle\Service\PathAttributeResolver\DoctrinePathAttributeResolver;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class PayseraRestExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('paysera_rest.locales', $config['locales']);
        if (count($config['locales']) === 0) {
            $container->removeDefinition('paysera_rest.listener.locale');
        }

        if ($config['validation']['property_path_converter'] !== null) {
            $container->setAlias(
                'paysera_rest.validation.property_path_converter',
                $config['validation']['property_path_converter']
            );
        }

        if (count($config['find_denormalizers']) > 0 && !class_exists('Doctrine\ORM\EntityManager')) {
            throw new RuntimeException(
                'Please install doctrine/orm before configuring paysera_rest.find_denormalizers'
            );
        }

        foreach ($config['find_denormalizers'] as $className => $denormalizerConfig) {
            $container->setDefinition(
                'paysera_rest.auto_registered.find_denormalizer.' . $className,
                $this->buildFindDenormalizerDefinition($className, $denormalizerConfig['field'])
            );
        }
    }

    private function buildFindDenormalizerDefinition(string $className, string $field): Definition
    {
        $repositoryDefinition = (new Definition(ObjectRepository::class, [$className]))
            ->setFactory([new Reference('doctrine.orm.entity_manager'), 'getRepository'])
        ;

        return (new Definition(DoctrinePathAttributeResolver::class, [
            $repositoryDefinition,
            $field,
        ]))->addTag('paysera_rest.path_attribute_resolver', ['type' => $className]);
    }
}
