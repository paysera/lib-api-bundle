<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Query implements RestAttributeInterface
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * @var string|null
     */
    private $denormalizationType;

    /**
     * @var string|null
     */
    private $denormalizationGroup;

    /**
     * @var Validation|null
     */
    private $validation;

    public function __construct(
        array $options = [],
        string $parameterName = null,
        ?string $denormalizationType = null,
        ?string $denormalizationGroup = null,
        ?Validation $validation = null
    ) {
        $this->setParameterName($options['parameterName'] ?? $parameterName);
        $this->setDenormalizationType($options['denormalizationType'] ?? $denormalizationType);
        $this->setDenormalizationGroup($options['denormalizationGroup'] ?? $denormalizationGroup);
        $this->setValidation($options['validation'] ?? $validation);
    }

    private function setParameterName(string $parameterName): self
    {
        $this->parameterName = $parameterName;
        return $this;
    }

    /**
     * @param string|null $denormalizationType
     * @return $this
     */
    private function setDenormalizationType($denormalizationType): self
    {
        $this->denormalizationType = $denormalizationType;
        return $this;
    }

    /**
     * @param string|null $denormalizationGroup
     * @return $this
     */
    public function setDenormalizationGroup($denormalizationGroup): self
    {
        $this->denormalizationGroup = $denormalizationGroup;
        return $this;
    }

    /**
     * @param Validation|null $validation
     * @return $this
     */
    private function setValidation($validation): self
    {
        $this->validation = $validation;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $resolverOptions = (new QueryResolverOptions())
            ->setParameterName($this->parameterName)
            ->setDenormalizationType($this->resolveDenormalizationType($reflectionMethod))
            ->setDenormalizationGroup($this->denormalizationGroup)
        ;

        $this->setValidationOptions($reflectionMethod, $resolverOptions);

        $options->addQueryResolverOptions($resolverOptions);
    }

    private function resolveDenormalizationType(ReflectionMethodWrapper $reflectionMethod): string
    {
        if ($this->denormalizationType !== null) {
            return $this->denormalizationType;
        }

        try {
            $typeName = $reflectionMethod->getNonBuiltInTypeForParameter($this->parameterName);
        } catch (ConfigurationException $exception) {
            throw new ConfigurationException(
                sprintf(
                    'Denormalization type could not be guessed for %s in %s',
                    '$' . $this->parameterName,
                    $reflectionMethod->getFriendlyName()
                )
            );
        }

        return $typeName;
    }

    private function setValidationOptions(ReflectionMethodWrapper $reflectionMethod, QueryResolverOptions $options): void
    {
        if ($this->validation === null) {
            return;
        }

        $restRequestOptions = new RestRequestOptions();
        $this->validation->apply($restRequestOptions, $reflectionMethod);

        if (!$restRequestOptions->isBodyValidationNeeded()) {
            $options->disableValidation();
            return;
        }

        $options->setValidationOptions($restRequestOptions->getBodyValidationOptions());
    }
}
