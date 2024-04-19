<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Attribute;

use Attribute;
use Paysera\Bundle\ApiBundle\Annotation\BodyContentType as BodyContentTypeAnnotation;

#[Attribute(Attribute::TARGET_METHOD)]
class BodyContentType extends BodyContentTypeAnnotation implements RestAttributeInterface
{
    public function __construct(
        array $supportedContentTypes,
        ?bool $jsonEncodedBody = null
    ) {
        parent::__construct([
            'supportedContentTypes' => $supportedContentTypes,
            'jsonEncodedBody' => $jsonEncodedBody,
        ]);
    }
}
