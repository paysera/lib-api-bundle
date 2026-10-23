<?php

declare(strict_types=1);

namespace App\Controller;

#[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
class MissingUseClassController
{
    public function show(): void
    {
    }
}
