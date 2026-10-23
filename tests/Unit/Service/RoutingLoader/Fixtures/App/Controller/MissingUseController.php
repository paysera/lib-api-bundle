<?php

declare(strict_types=1);

namespace App\Controller;

use OpenApi\Attributes as OA;

class MissingUseController
{
    #[Body(parameterName: 'body')]
    public function showBody(): void
    {
    }

    #[BodyContentType(supportedContentTypes: ['application/json'])]
    public function showBodyContentType(): void
    {
    }

    #[PathAttribute(parameterName: 'id', pathPartName: 'id')]
    public function showPathAttribute(): void
    {
    }

    #[Query(parameterName: 'filter')]
    public function showQuery(): void
    {
    }

    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function showRequiredPermissions(): void
    {
    }

    #[ResponseNormalization(normalizationType: 'user')]
    public function showResponseNormalization(): void
    {
    }

    #[Validation(groups: ['Default'])]
    public function showValidation(): void
    {
    }

    #[requiredpermissions(permissions: ['ROLE_ADMIN'])]
    public function showLowercaseName(): void
    {
    }

    #[Rest\RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function showQualifiedName(): void
    {
    }

    #[Get]
    public function showMissingClassWithOtherName(): void
    {
    }

    #[OA\Query(name: 'filter')]
    public function showUninstalledPackageAttribute(): void
    {
    }
}
