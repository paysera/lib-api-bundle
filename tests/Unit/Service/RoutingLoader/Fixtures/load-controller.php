<?php

declare(strict_types=1);

use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;

require $argv[1];

$loader = new RoutingAttributeLoader();
$loader->setRequestHelper(new RestRequestHelper(new RestRequestOptionsRegistry()));
$loader->setRestRequestAnnotationOptionsBuilder(
    new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
);
$loader->setRestRequestAttributeOptionsBuilder(
    new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class))
);

try {
    $loader->load($argv[2]);
    echo 'loaded';
} catch (Throwable $exception) {
    echo get_class($exception) . ': ' . $exception->getMessage();
}
