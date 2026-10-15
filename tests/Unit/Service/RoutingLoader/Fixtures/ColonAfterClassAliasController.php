<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions as Perm;
use Symfony\Component\Routing\Attribute\Route;

class ColonAfterClassAliasController
{
    /**
     * @Perm:admin(permissions={"ROLE_ADMIN"})
     */
    #[Route('/colon-after-class-alias', methods: ['GET'])]
    public function show()
    {
    }
}
