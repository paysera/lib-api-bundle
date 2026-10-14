<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class FirstNameSharedController
{
    /**
     * @Paysera\Audited
     * @Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/first-name-shared', methods: ['GET'])]
    public function show()
    {
    }
}
