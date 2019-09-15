<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Annotation;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Entity\ValidationOptions;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Validation implements RestAnnotationInterface
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

    public function __construct(array $options)
    {
        $this->setGroups($options['groups'] ?? [Constraint::DEFAULT_GROUP]);
        $this->setViolationPathMap($options['violationPathMap'] ?? []);
        $this->setEnabled($options['enabled'] ?? true);
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

    public function isSeveralSupported(): bool
    {
        return true;
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
