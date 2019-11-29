<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle;

use Paysera\Component\DependencyInjection\AddTaggedCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PayseraApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddTaggedCompilerPass(
            'paysera_api.rest_request_options_registry',
            'paysera_api.request_options',
            'registerRestRequestOptions',
            ['controller']
        ));

        $container->addCompilerPass(new AddTaggedCompilerPass(
            'paysera_api.path_attribute_resolver_registry',
            'paysera_api.path_attribute_resolver',
            'registerPathAttributeResolver',
            ['type']
        ));
    }
}
