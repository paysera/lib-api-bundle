<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class BackslashBeforeTagDocblockController
{
    /**
     * Lists the exports under C:\exports\
     * @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/backslash-before-tag', methods: ['GET'])]
    public function show(): void
    {
    }
}
