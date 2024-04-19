<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\Body as BodyAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class Body extends BodyAnnotation implements RestAttributeInterface
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
