<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle as Api;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("Api")
 */
class IgnoredImportedAliasSpacedController
{
    /**
     * @Api \Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/ignored-imported-alias-spaced', methods: ['GET'])]
    public function show()
    {
    }
}
