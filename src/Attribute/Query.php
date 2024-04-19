<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\Query as QueryAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Query extends QueryAnnotation implements RestAttributeInterface
{
    public function __construct(
        string $parameterName,
        ?string $denormalizationType = null,
        ?string $denormalizationGroup = null,
        ?bool $optional = null
    ) {
        parent::__construct([
            'parameterName' => $parameterName,
            'denormalizationType' => $denormalizationType,
            'denormalizationGroup' => $denormalizationGroup,
            'optional' => $optional,
        ]);
    }
}
