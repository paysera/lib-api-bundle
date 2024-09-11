<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain as LegacyMappingDriverChain;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class PayseraFixtureTestExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $prefix = Kernel::MAJOR_VERSION <= 4 ? 'legacy_' : '';
        $loader->load($prefix . 'services.xml');

        $this->handleDeprecations($container, $loader);
    }

    public function handleDeprecations(ContainerBuilder $container, Loader\XmlFileLoader $loader): void
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

        if (!class_exists(LegacyMappingDriverChain::class)) {
            $container->setParameter('doctrine.orm.metadata.driver_chain.class', MappingDriverChain::class);
        }
    }
}
