<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Service\Annotation\ReflectionMethodWrapper;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequiredPermissions implements RestAttributeInterface
{
    /**
     * @var array
     */
    private $permissions;

    public function __construct(
        array $data = [],
        array $permissions = null
    ) {
        $this->setPermissions($data['permissions'] ?? $permissions);
    }

    private function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function apply(RestRequestOptions $options, ReflectionMethodWrapper $reflectionMethod): void
    {
        $options->setRequiredPermissions(
            array_unique(array_merge($options->getRequiredPermissions(), $this->permissions))
        );
    }
}
