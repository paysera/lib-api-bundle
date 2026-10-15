<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class FullyQualifiedMiscasedTagController
{
    /**
     * @\Paysera\Bundle\ApiBundle\Annotation\requiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/fully-qualified-miscased-tag', methods: ['GET'])]
    public function show()
    {
    }
}
