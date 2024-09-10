<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

#[Attribute(Attribute::TARGET_METHOD)]
class ResponseNormalization implements RestAttributeInterface
{
    /**
     * @var string|null
     */
    private $normalizationType;

    /**
     * @var string|null
     */
    private $normalizationGroup;

    public function __construct(
        array $options = [],
        ?string $normalizationType = null,
        ?string $normalizationGroup = null
    ) {
        $this->setNormalizationType($options['normalizationType'] ?? $normalizationType);
        $this->setNormalizationGroup($options['normalizationGroup'] ?? $normalizationGroup);
    }

    private function setNormalizationType(?string $normalizationType): self
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

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $options->setResponseNormalizationType($this->normalizationType);
        $options->setResponseNormalizationGroup($this->normalizationGroup);
    }
}
