<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use ReflectionMethod;

/**
 * @internal
 */
class RestRequestAttributeOptionsBuilder
{
    /**
     * @var RestRequestOptionsValidator
     */
    private $validator;

    public function __construct(RestRequestOptionsValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param RestAttributeInterface[] $attributes
     * @param ReflectionMethod $reflectionMethod
     * @return RestRequestOptions
     */
    public function buildOptions(array $attributes, ReflectionMethod $reflectionMethod): RestRequestOptions
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
