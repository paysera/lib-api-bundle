<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class AliasAndBareDuplicateController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_USER"})
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/alias-and-bare-duplicate', methods: ['GET'])]
    public function show()
    {
    }
}
