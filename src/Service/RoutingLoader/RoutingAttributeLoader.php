<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
class RoutingAttributeLoader extends AttributeRouteControllerLoader
{
    /**
     * @var RestRouteConfigurator
     */
    private $restRouteConfigurator;

    public function setRestRouteConfigurator(RestRouteConfigurator $restRouteConfigurator)
    {
        $this->restRouteConfigurator = $restRouteConfigurator;
    }

    protected function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        object $annot
    ): void {
        parent::configureRoute($route, $class, $method, $annot);

        $this->restRouteConfigurator->configureRoute($route, $class, $method, $this->reader ?? null);
    }
}
