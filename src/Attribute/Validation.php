<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Entity\ValidationOptions;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Validation implements RestAttributeInterface
{
    /**
     * @var array
     */
    private $groups;

    /**
     * @var array
     */
    private $violationPathMap;

    /**
     * @var bool
     */
    private $enabled;

    public function __construct(
        array $options = [],
        array $groups = [Constraint::DEFAULT_GROUP],
        array $violationPathMap = [],
        bool $enabled = true
    ) {
        $this->setGroups($options['groups'] ?? $groups);
        $this->setViolationPathMap($options['violationPathMap'] ?? $violationPathMap);
        $this->setEnabled($options['enabled'] ?? $enabled);
    }

    private function setGroups(array $groups): self
    {
        $this->groups = $groups;
        return $this;
    }

    private function setViolationPathMap(array $violationPathMap): self
    {
        $this->violationPathMap = $violationPathMap;
        return $this;
    }

    private function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod)
    {
        if (!$this->enabled) {
            $options->disableBodyValidation();
            return;
        }

        $options->setBodyValidationOptions(
            (new ValidationOptions())
                ->setValidationGroups($this->groups)
                ->setViolationPathMap($this->violationPathMap)
        );
    }
}
