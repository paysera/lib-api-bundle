<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use App\Controller\Body;
use App\Controller\BodyContentType;
use App\Controller\PathAttribute;
use App\Controller\Query;
use App\Controller\RequiredPermissions;
use App\Controller\requiredpermissions as LowercaseRequiredPermissions;
use App\Controller\ResponseNormalization;
use App\Controller\Validation;
use RequiredPermissions as GlobalRequiredPermissions;

class MissingAttributeClassController
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

    #[LowercaseRequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function showLowercaseName(): void
    {
    }

    #[GlobalRequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function showGlobalName(): void
    {
    }
}
