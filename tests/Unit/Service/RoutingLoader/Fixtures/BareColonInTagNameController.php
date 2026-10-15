<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class BareColonInTagNameController
{
    /**
     * @RequiredPermissions:admin(permissions={"ROLE_ADMIN"})
     */
    #[Route('/bare-colon-in-tag-name', methods: ['GET'])]
    public function show()
    {
    }
}
