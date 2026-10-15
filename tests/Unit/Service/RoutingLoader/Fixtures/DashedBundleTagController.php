<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class DashedBundleTagController
{
    /**
     * @Rest\RequiredPermissions-legacy
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/dashed-bundle-tag', methods: ['GET'])]
    public function show()
    {
    }
}
