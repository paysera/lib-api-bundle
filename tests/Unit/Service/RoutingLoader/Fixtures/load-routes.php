<?php

declare(strict_types=1);

use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;

require $argv[1];

if (!extension_loaded('Zend OPcache')) {
    echo 'no opcache';
    exit;
}

$loader = new RoutingAttributeLoader();
$loader->setRequestHelper(new RestRequestHelper(new RestRequestOptionsRegistry()));
$loader->setRestRequestAnnotationOptionsBuilder(
    new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
);
$loader->setRestRequestAttributeOptionsBuilder(
    new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
);

try {
    foreach ($loader->load(DocblockOptionsOnAttributeRouteController::class)->all() as $route) {
        $options = unserialize($route->getDefault(RestRequestHelper::SERIALIZED_REST_OPTIONS_KEY) ?? 'N;');
        echo json_encode($options === null ? null : $options->getRequiredPermissions());
    }
} catch (Throwable $exception) {
    echo get_class($exception);
}
