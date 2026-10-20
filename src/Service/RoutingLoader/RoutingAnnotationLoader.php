<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Routing\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
class RoutingAnnotationLoader extends AnnotatedRouteControllerLoader
{
    /**
     * @var RestRouteConfigurator
     */
    private $restRouteConfigurator;

    public function setRestRouteConfigurator(RestRouteConfigurator $restRouteConfigurator)
    {
        $this->restRouteConfigurator = $restRouteConfigurator;
    }

    protected function configureRoute(Route $route, ReflectionClass $class, ReflectionMethod $method, $routeAnnotation)
    {
        parent::configureRoute($route, $class, $method, $routeAnnotation);

        $this->restRouteConfigurator->configureRoute($route, $class, $method, $this->reader);
    }
}
