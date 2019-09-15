<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class BodyContentType implements RestAnnotationInterface
{
    /**
     * @var array
     */
    private $supportedContentTypes;

    /**
     * @var bool
     */
    private $jsonEncodedBody;

    public function __construct(array $options)
    {
        $this->setSupportedContentTypes($options['supportedContentTypes']);
        $this->setJsonEncodedBody($options['jsonEncodedBody'] ?? false);
    }

    /**
     * @param array $supportedContentTypes
     * @return $this
     */
    private function setSupportedContentTypes(array $supportedContentTypes): self
    {
        $this->supportedContentTypes = $supportedContentTypes;
        return $this;
    }

    /**
     * @param bool $jsonEncodedBody
     * @return $this
     */
    private function setJsonEncodedBody(bool $jsonEncodedBody): self
    {
        $this->jsonEncodedBody = $jsonEncodedBody;
        return $this;
    }

    public function isSeveralSupported(): bool
    {
        return false;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        $options->setSupportedContentTypes($this->supportedContentTypes, $this->jsonEncodedBody);
    }
}
