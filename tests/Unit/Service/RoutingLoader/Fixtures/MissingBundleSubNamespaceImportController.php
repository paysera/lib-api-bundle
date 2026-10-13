<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Rest\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class MissingBundleSubNamespaceImportController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/missing-bundle-sub-namespace-import', methods: ['GET'])]
    public function show()
    {
    }
}
