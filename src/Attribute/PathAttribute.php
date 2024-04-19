<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute as PathAttributeAnnotation;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PathAttribute extends PathAttributeAnnotation implements RestAttributeInterface
{
    public function __construct(
        string $parameterName,
        string $pathPartName,
        ?string $resolverType = null,
        ?bool $resolutionMandatory = null
    ) {
        parent::__construct([
            'parameterName' => $parameterName,
            'pathPartName' => $pathPartName,
            'resolverType' => $resolverType,
            'resolutionMandatory' => $resolutionMandatory,
        ]);
    }
}
