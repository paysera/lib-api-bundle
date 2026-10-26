<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class UnimportedNoteTagController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @note kept for older clients
     */
    #[Route('/unimported-note-tag', methods: ['GET'])]
    public function show(): void
    {
    }
}
