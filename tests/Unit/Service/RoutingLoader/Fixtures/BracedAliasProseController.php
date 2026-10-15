<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class BracedAliasProseController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     *
     * The {@Rest} annotations above guard the route.
     */
    #[Route('/braced-alias-prose', methods: ['GET'])]
    public function show()
    {
    }
}
