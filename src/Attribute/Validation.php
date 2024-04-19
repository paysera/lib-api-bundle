<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\Validation as ValidationAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Validation extends ValidationAnnotation
{
    public function __construct(
        ?array $groups = null,
        ?array $violationPathMap = null,
        ?bool $enabled = null
    ) {
        parent::__construct([
            'groups' => $groups,
            'violationPathMap' => $violationPathMap,
            'enabled' => $enabled,
        ]);
    }
}
