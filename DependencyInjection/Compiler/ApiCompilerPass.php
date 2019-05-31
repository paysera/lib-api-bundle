<?php

namespace Paysera\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ApiCompilerPass implements CompilerPassInterface
{

    /**
     * Run the Compiler and process all Passes.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->processTags($container, 'paysera_rest.encoder', 'format', 'addEncoder');
        $this->processTags($container, 'paysera_rest.decoder', 'format', 'addDecoder');
        $this->processTags($container, 'paysera_rest.api', 'uri_pattern', 'addApiByUriPattern', true);
        $this->processTags($container, 'paysera_rest.api', 'api_key', 'addApiByKey', true);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $tag
     * @param string           $attributeName
     * @param string           $methodName
     * @param boolean          $ignoreOnNoAttribute
     *
     * @throws InvalidConfigurationException
     */
    protected function processTags($container, $tag, $attributeName, $methodName, $ignoreOnNoAttribute = false)
    {
        if (!$container->hasDefinition('paysera_rest.api_manager')) {
            return;
        }
        $definition = $container->getDefinition('paysera_rest.api_manager');
        foreach ($container->findTaggedServiceIds($tag) as $id => $tags) {
            if (count($tags) > 1) {
                $exception = new InvalidConfigurationException(
                    'Service ' . $id . ' cannot have more than one tag ' . $tag
                );
                $exception->setPath($id);
                throw $exception;
            }
            $attributes = $tags[0];
            if (empty($attributes[$attributeName])) {
                if (!$ignoreOnNoAttribute) {
                    $exception = new InvalidConfigurationException(
                        'Service ' . $id . ' tag ' . $tag . ' is missing attribute ' . $attributeName
                    );
                    $exception->setPath($id);
                    throw $exception;
                }
            } else {
                $definition->addMethodCall($methodName, array(new Reference($id), $attributes[$attributeName]));
            }
        }
    }

}
