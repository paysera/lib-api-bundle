<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Annotation;

use Paysera\Bundle\ApiBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Query implements RestAnnotationInterface
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

    public function __construct(array $options)
    {
        $this->setParameterName($options['parameterName']);
        $this->setDenormalizationType($options['denormalizationType'] ?? null);
        $this->setDenormalizationGroup($options['denormalizationGroup'] ?? null);
        $this->setValidation($options['validation'] ?? null);
    }

    /**
     * @param string $parameterName
     * @return $this
     */
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

    public function isSeveralSupported(): bool
    {
        return true;
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
            throw new ConfigurationException(sprintf(
                'Denormalization type could not be guessed for %s in %s',
                '$' . $this->parameterName,
                $reflectionMethod->getFriendlyName()
            ));
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
