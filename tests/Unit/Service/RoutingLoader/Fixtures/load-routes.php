<?php

declare(strict_types=1);

use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationGuard;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppPermission;

require __DIR__ . '/../../../../../vendor/autoload.php';

$loader = new RoutingAttributeLoader();
$loader->setRestRouteConfigurator(new RestRouteConfigurator(
    new RestRequestHelper(new RestRequestOptionsRegistry()),
    new RestRequestAnnotationOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
    new RestRequestAttributeOptionsBuilder(Mockery::spy(RestRequestOptionsValidator::class)),
    new DocblockAnnotationGuard()
));

$results = ['doc_comments_stripped' => (new ReflectionClass(AppPermission::class))->getDocComment() === false];
foreach (array_slice($argv, 1) as $controller) {
    try {
        $permissions = [];
        foreach ($loader->load($controller)->all() as $route) {
            $options = $route->getDefault(RestRequestHelper::SERIALIZED_REST_OPTIONS_KEY);
            $permissions[] = $options === null ? null : unserialize($options)->getRequiredPermissions();
        }
        $results[$controller] = $permissions;
    } catch (ConfigurationException $exception) {
        $results[$controller] = $exception->getMessage();
    }
}

echo json_encode($results);
