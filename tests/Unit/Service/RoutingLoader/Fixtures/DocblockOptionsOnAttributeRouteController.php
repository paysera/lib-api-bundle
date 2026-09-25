<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class DocblockOptionsOnAttributeRouteController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/docblock-options', methods: ['GET'])]
    public function show()
    {
    }
}
