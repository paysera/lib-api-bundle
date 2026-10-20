<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class NamespaceMentionedInStringController
{
    // replaces @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions
    private const MIGRATED_FROM = '@\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions';

    /**
     * @note kept for older clients
     */
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/namespace-mentioned-in-string', methods: ['GET'])]
    public function show(): void
    {
    }
}
