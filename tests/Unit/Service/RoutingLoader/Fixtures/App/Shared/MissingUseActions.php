<?php

declare(strict_types=1);

namespace App\Shared;

trait MissingUseActions
{
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function showFromTrait(): void
    {
    }
}
