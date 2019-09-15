<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Entity;

use RuntimeException;

class PathAttributeResolverOptions
{
    /**
     * @var string|null
     */
    private $parameterName;

    /**
     * @var string|null
     */
    private $pathPartName;

    /**
     * @var string|null
     */
    private $denormalizationType;

    /**
     * @var bool
     */
    private $resolutionMandatory;

    public function __construct()
    {
        $this->resolutionMandatory = true;
    }

    public function setParameterName(string $parameterName): self
    {
        $this->parameterName = $parameterName;
        return $this;
    }

    public function setPathPartName(string $pathPartName): self
    {
        $this->pathPartName = $pathPartName;
        return $this;
    }

    public function setDenormalizationType(string $denormalizationType): self
    {
        $this->denormalizationType = $denormalizationType;
        return $this;
    }

    public function getParameterName(): string
    {
        if ($this->parameterName === null) {
            throw new RuntimeException('parameterName was not set');
        }
        return $this->parameterName;
    }

    public function getPathPartName(): string
    {
        if ($this->pathPartName === null) {
            throw new RuntimeException('pathPartName was not set');
        }
        return $this->pathPartName;
    }

    public function getDenormalizationType(): string
    {
        if ($this->denormalizationType === null) {
            throw new RuntimeException('denormalizationType was not set');
        }
        return $this->denormalizationType;
    }

    /**
     * @return bool
     */
    public function isResolutionMandatory(): bool
    {
        return $this->resolutionMandatory;
    }

    /**
     * @param bool $resolutionMandatory
     * @return $this
     */
    public function setResolutionMandatory(bool $resolutionMandatory): self
    {
        $this->resolutionMandatory = $resolutionMandatory;
        return $this;
    }
}
