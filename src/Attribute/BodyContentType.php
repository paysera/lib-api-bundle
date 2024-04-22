<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;

#[Attribute(Attribute::TARGET_METHOD)]
class BodyContentType implements RestAttributeInterface
{
    /**
     * @var array
     */
    private $supportedContentTypes;

    /**
     * @var bool
     */
    private $jsonEncodedBody;

    public function __construct(
        array $data = [],
        array $supportedContentTypes = null,
        bool $jsonEncodedBody = false
    ) {
        $this->setSupportedContentTypes($data['supportedContentTypes'] ?? $supportedContentTypes);
        $this->setJsonEncodedBody($data['jsonEncodedBody'] ?? $jsonEncodedBody);
    }

    private function setSupportedContentTypes(array $supportedContentTypes): self
    {
        $this->supportedContentTypes = $supportedContentTypes;
        return $this;
    }

    private function setJsonEncodedBody(bool $jsonEncodedBody): self
    {
        $this->jsonEncodedBody = $jsonEncodedBody;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void
    {
        $options->setSupportedContentTypes($this->supportedContentTypes, $this->jsonEncodedBody);
    }
}
