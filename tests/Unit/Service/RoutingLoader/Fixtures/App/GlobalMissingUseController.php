<?php

declare(strict_types=1);

use OpenApi\Attributes as OA;

class GlobalMissingUseController
{
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function show(): void
    {
    }

    #[OA\Query(name: 'filter')]
    public function showUninstalledPackageAttribute(): void
    {
    }
}
