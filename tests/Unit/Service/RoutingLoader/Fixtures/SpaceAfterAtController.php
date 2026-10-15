<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class SpaceAfterAtController
{
    /**
     * Mail the owners @ Rest\RequiredPermissions for access.
     *
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/space-after-at', methods: ['GET'])]
    public function show()
    {
    }
}
