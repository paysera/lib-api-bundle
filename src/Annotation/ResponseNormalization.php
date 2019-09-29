<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ResponseNormalization implements RestAnnotationInterface
{
    /**
     * @var string|null
     */
    private $normalizationType;

    /**
     * @var string|null
     */
    private $normalizationGroup;

    public function __construct(array $options)
    {
        $this->setNormalizationType($options['normalizationType'] ?? null);
        $this->setNormalizationGroup($options['normalizationGroup'] ?? null);
    }

    /**
     * @param string|null $normalizationType
     * @return $this
     */
    private function setNormalizationType($normalizationType): self
    {
        $this->normalizationType = $normalizationType;
        return $this;
    }

    /**
     * @param string|null $normalizationGroup
     * @return $this
     */
    public function setNormalizationGroup($normalizationGroup): self
    {
        $this->normalizationGroup = $normalizationGroup;
        return $this;
    }

    public function isSeveralSupported(): bool
    {
        return false;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $options->setResponseNormalizationType($this->normalizationType);
        $options->setResponseNormalizationGroup($this->normalizationGroup);
    }
}
