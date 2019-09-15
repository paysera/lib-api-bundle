<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Entity;

use RuntimeException;

class RestRequestOptions
{
    /**
     * @var string|null
     */
    private $bodyDenormalizationType;

    /**
     * @var string|null
     */
    private $bodyParameterName;

    /**
     * @var array of string
     */
    private $supportedContentTypes;

    /**
     * @var bool
     */
    private $jsonEncodedBody;

    /**
     * @var bool
     */
    private $bodyOptional;

    /**
     * @var string|null
     */
    private $responseNormalizationType;

    /**
     * @var array
     */
    private $requiredPermissions;

    /**
     * @var array|PathAttributeResolverOptions[]
     */
    private $pathAttributeResolverOptionsList;

    /**
     * @var array|QueryResolverOptions[]
     */
    private $queryResolverOptionsList;

    /**
     * @var ValidationOptions|null
     */
    private $bodyValidationOptions;

    public function __construct()
    {
        $this->supportedContentTypes = ['', 'application/json'];
        $this->jsonEncodedBody = true;
        $this->bodyOptional = false;
        $this->requiredPermissions = [];
        $this->pathAttributeResolverOptionsList = [];
        $this->queryResolverOptionsList = [];
        $this->bodyValidationOptions = new ValidationOptions();
    }

    public function setBodyDenormalizationType(string $bodyDenormalizationType): self
    {
        $this->bodyDenormalizationType = $bodyDenormalizationType;
        return $this;
    }

    public function setBodyParameterName(string $bodyParameterName): self
    {
        $this->bodyParameterName = $bodyParameterName;
        return $this;
    }

    public function setSupportedContentTypes(array $supportedContentTypes, bool $jsonEncodedBody = false): self
    {
        $this->supportedContentTypes = $supportedContentTypes;
        $this->jsonEncodedBody = $jsonEncodedBody;
        return $this;
    }

    public function setBodyOptional(bool $bodyOptional): self
    {
        $this->bodyOptional = $bodyOptional;
        return $this;
    }

    /**
     * @param string|null $responseNormalizationType
     * @return $this
     */
    public function setResponseNormalizationType($responseNormalizationType): self
    {
        $this->responseNormalizationType = $responseNormalizationType;
        return $this;
    }

    /**
     * @param array $requiredPermissions array of string
     * @return $this
     */
    public function setRequiredPermissions(array $requiredPermissions): self
    {
        $this->requiredPermissions = $requiredPermissions;
        return $this;
    }

    public function addPathAttributeResolverOptions(PathAttributeResolverOptions $pathAttributeResolverOptions): self
    {
        $this->pathAttributeResolverOptionsList[] = $pathAttributeResolverOptions;
        return $this;
    }

    public function addQueryResolverOptions(QueryResolverOptions $queryResolverOptions): self
    {
        $this->queryResolverOptionsList[] = $queryResolverOptions;
        return $this;
    }

    public function setBodyValidationOptions(ValidationOptions $bodyValidationOptions): self
    {
        $this->bodyValidationOptions = $bodyValidationOptions;
        return $this;
    }

    public function hasBodyDenormalization(): bool
    {
        return $this->bodyDenormalizationType !== null && $this->bodyParameterName !== null;
    }

    public function getBodyDenormalizationType(): string
    {
        if ($this->bodyDenormalizationType === null) {
            throw new RuntimeException(
                'No bodyDenormalizationType available, call hasBodyDenormalization beforehand'
            );
        }
        return $this->bodyDenormalizationType;
    }

    public function getBodyParameterName(): string
    {
        if ($this->bodyParameterName === null) {
            throw new RuntimeException(
                'No bodyParameterName available, call hasBodyDenormalization beforehand'
            );
        }
        return $this->bodyParameterName;
    }

    public function getSupportedRequestContentTypes(): array
    {
        return $this->supportedContentTypes;
    }

    public function isJsonEncodedBody(): bool
    {
        return $this->jsonEncodedBody;
    }

    public function isBodyOptional(): bool
    {
        return $this->bodyOptional;
    }

    /**
     * @return string|null
     */
    public function getResponseNormalizationType()
    {
        return $this->responseNormalizationType;
    }

    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    /**
     * @return array|PathAttributeResolverOptions[]
     */
    public function getPathAttributeResolverOptionsList(): array
    {
        return $this->pathAttributeResolverOptionsList;
    }

    /**
     * @return array|QueryResolverOptions[]
     */
    public function getQueryResolverOptionsList(): array
    {
        return $this->queryResolverOptionsList;
    }

    public function isBodyValidationNeeded(): bool
    {
        return $this->bodyValidationOptions !== null && $this->bodyValidationOptions->isEnabled();
    }

    public function disableBodyValidation(): self
    {
        $this->bodyValidationOptions = null;
        return $this;
    }

    public function getBodyValidationOptions(): ValidationOptions
    {
        if ($this->bodyValidationOptions === null) {
            throw new RuntimeException('No bodyValidationOptions available, call isBodyValidationNeeded beforehand');
        }
        return $this->bodyValidationOptions;
    }
}
