<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class ProseWithAliasTagController
{
    /**
     * Replaces the old `@Api\Body` annotation; the body is read as JSON (@Api\Body in v1).
     *
     * @deprecated "Filter with @Api\Query on the new endpoint"; the old `@Api\Query`
     *     (@Api\Query in v1) stays readable
     *
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/prose-with-alias-tag', methods: ['GET'])]
    public function show()
    {
    }
}
