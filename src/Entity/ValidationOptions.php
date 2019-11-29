<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Entity;

use Symfony\Component\Validator\Constraint;

class ValidationOptions
{
    /**
     * @var array of string
     */
    private $validationGroups;

    /**
     * @var array of string, associative
     */
    private $violationPathMap;

    public function __construct()
    {
        $this->validationGroups = [Constraint::DEFAULT_GROUP];
        $this->violationPathMap = [];
    }

    public function setValidationGroups(array $validationGroups): self
    {
        $this->validationGroups = $validationGroups;
        return $this;
    }

    public function setViolationPathMap(array $violationPathMap): self
    {
        $this->violationPathMap = $violationPathMap;
        return $this;
    }

    public function getValidationGroups(): array
    {
        return $this->validationGroups;
    }

    public function isEnabled(): bool
    {
        return count($this->validationGroups) > 0;
    }

    public function getViolationPathMap(): array
    {
        return $this->violationPathMap;
    }
}
