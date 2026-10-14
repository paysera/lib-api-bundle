<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class MiscasedAliasMisspelledController
{
    /**
     * @Rest\RequiredPermission(permissions={"ROLE_ADMIN"})
     */
    #[Route('/miscased-alias-misspelled', methods: ['GET'])]
    public function show()
    {
    }
}
