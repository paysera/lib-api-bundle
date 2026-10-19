<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("note")
 */
class IgnoredNoteTagController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @note kept for older clients
     */
    #[Route('/ignored-note-tag', methods: ['GET'])]
    public function show()
    {
    }
}
