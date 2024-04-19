<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization as ResponseNormalizationAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class ResponseNormalization extends ResponseNormalizationAnnotation implements RestAttributeInterface
{
    public function __construct(
        ?string $normalizationType = null,
        ?string $normalizationGroup = null
    ) {
        parent::__construct([
            'normalizationType' => $normalizationType,
            'normalizationGroup' => $normalizationGroup,
        ]);
    }
}
