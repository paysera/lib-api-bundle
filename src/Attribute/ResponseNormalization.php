<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;

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
        array $data = [],
        ?string $normalizationType = null,
        ?string $normalizationGroup = null
    )
    {
        $this->setNormalizationType($data['normalizationType'] ?? $normalizationType);
        $this->setNormalizationGroup($data['normalizationGroup'] ?? $normalizationGroup);
    }

    private function setNormalizationType(?string $normalizationType): self
    {
        $this->normalizationType = $normalizationType;
        return $this;
    }

    public function setNormalizationGroup(?string $normalizationGroup): self
    {
        $this->normalizationGroup = $normalizationGroup;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void
    {
        $options->setResponseNormalizationType($this->normalizationType);
        $options->setResponseNormalizationGroup($this->normalizationGroup);
    }
}
