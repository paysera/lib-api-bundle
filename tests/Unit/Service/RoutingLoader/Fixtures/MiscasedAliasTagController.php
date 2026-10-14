<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class MiscasedAliasTagController
{
    /**
     * @Rest\requiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/miscased-alias-tag', methods: ['GET'])]
    public function show()
    {
    }
}
