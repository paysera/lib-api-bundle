<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions as RequiredPermissionsAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequiredPermissions extends RequiredPermissionsAnnotation implements RestAttributeInterface
{
    public function __construct(array $permissions)
    {
        parent::__construct([
            'permissions' => $permissions,
        ]);
    }
}
