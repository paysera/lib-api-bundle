<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class DashAfterAliasProseController
{
    /**
     * Lists users for the @Rest-API clients.
     *
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/dash-after-alias-prose', methods: ['GET'])]
    public function show()
    {
    }
}
