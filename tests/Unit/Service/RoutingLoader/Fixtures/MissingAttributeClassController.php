<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

class MissingAttributeClassController
{
    #[\Body(parameterName: 'body')]
    public function body(): void
    {
    }

    #[\BodyContentType(supportedContentTypes: ['application/json'])]
    public function bodyContentType(): void
    {
    }

    #[\PathAttribute(parameterName: 'id', pathPartName: 'id')]
    public function pathAttribute(): void
    {
    }

    #[\Query(parameterName: 'filter')]
    public function query(): void
    {
    }

    #[\RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function requiredPermissions(): void
    {
    }

    #[\ResponseNormalization(normalizationType: 'user')]
    public function responseNormalization(): void
    {
    }

    #[\Validation(groups: ['Default'])]
    public function validation(): void
    {
    }

    #[\requiredpermissions(permissions: ['ROLE_ADMIN'])]
    public function lowercaseName(): void
    {
    }
}
