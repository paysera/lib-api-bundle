<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Security;
use Symfony\Component\Routing\Attribute\Route;

class NamespaceAliasReferencesDocblockController
{
    /**
     * @RequiredPermissions(permissions={Security\Roles::ADMIN})
     */
    #[Route('/namespace-alias-references', methods: ['GET'])]
    public function show()
    {
    }
}
