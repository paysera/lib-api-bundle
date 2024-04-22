<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;

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
        array $data = [],
        string $parameterName = null,
        ?string $denormalizationType = null,
        ?string $denormalizationGroup = null,
        ?Validation $validation = null
    ) {
        $this->setParameterName($data['parameterName'] ?? $parameterName);
        $this->setDenormalizationType($data['denormalizationType'] ?? $denormalizationType);
        $this->setDenormalizationGroup($data['denormalizationGroup'] ?? $denormalizationGroup);
        $this->setValidation($data['validation'] ?? $validation);
    }

    private function setParameterName(string $parameterName): self
    {
        $this->parameterName = $parameterName;
        return $this;
    }

    private function setDenormalizationType(?string $denormalizationType): self
    {
        $this->denormalizationType = $denormalizationType;
        return $this;
    }

    public function setDenormalizationGroup(?string $denormalizationGroup): self
    {
        $this->denormalizationGroup = $denormalizationGroup;
        return $this;
    }

    private function setValidation(?Validation $validation): self
    {
        $this->validation = $validation;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void
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

    private function setValidationOptions(ReflectionMethodWrapper $reflectionMethod, QueryResolverOptions $options)
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
