<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader as Loader;
use Symfony\Component\Routing\Attribute\Route;

class NamespaceAliasPathReferencesDocblockController
{
    /**
     * @RequiredPermissions(permissions={Loader\Fixtures\Support\Roles::ADMIN})
     */
    #[Route('/namespace-alias-path-references', methods: ['GET'])]
    public function show()
    {
    }
}
