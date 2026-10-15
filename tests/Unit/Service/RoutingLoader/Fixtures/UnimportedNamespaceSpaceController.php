<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class UnimportedNamespaceSpaceController
{
    /**
     * @Paysera\Bundle\ApiBundle\Annotation RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/unimported-namespace-space', methods: ['GET'])]
    public function show()
    {
    }
}
