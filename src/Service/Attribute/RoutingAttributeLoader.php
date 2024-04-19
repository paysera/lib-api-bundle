<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\Attribute;

use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
class RoutingAttributeLoader extends AttributeRouteControllerLoader
{
    public function __construct(
        private RestRequestHelper $restRequestHelper,
        private RestRequestOptionsValidator $validator,
        protected $env = null
    ) {
        parent::__construct($env);
    }

    protected function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        object $annot
    ): void {
        parent::configureRoute($route, $class, $method, $annot);

        $attributes = array_merge($class->getAttributes(), $method->getAttributes());

        $restAttributes = [];
        foreach ($attributes as $attribute) {
            if (is_subclass_of($attribute->getName(), RestAttributeInterface::class)) {
                /** @var RestAttributeInterface $instance */
                $instance = $attribute->newInstance();
                $restAttributes[] = $instance;
            }
        }

        if ($restAttributes === []) {
            return;
        }

        $this->restRequestHelper->setOptionsForRoute($route, $this->buildOptions($restAttributes, $method));
    }

    /**
     * @param RestAttributeInterface[] $attributes
     * @param ReflectionMethod $reflectionMethod
     * @return RestRequestOptions
     */
    private function buildOptions(array $attributes, ReflectionMethod $reflectionMethod): RestRequestOptions
    {
        $options = new RestRequestOptions();
        $wrapper = new ReflectionMethodWrapper($reflectionMethod);

        foreach ($attributes as $attribute) {
            $attribute->apply($options, $wrapper);
        }

        $this->validator->validateRestRequestOptions($options, $wrapper->getFriendlyName());

        return $options;
    }
}
