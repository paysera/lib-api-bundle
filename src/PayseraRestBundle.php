<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle;

use Paysera\Component\DependencyInjection\AddTaggedCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PayseraRestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddTaggedCompilerPass(
            'paysera_rest.rest_request_options_registry',
            'paysera_rest.request_options',
            'registerRestRequestOptions',
            ['controller']
        ));

        $container->addCompilerPass(new AddTaggedCompilerPass(
            'paysera_rest.path_attribute_resolver_registry',
            'paysera_rest.path_attribute_resolver',
            'registerPathAttributeResolver',
            ['type']
        ));
    }
}
