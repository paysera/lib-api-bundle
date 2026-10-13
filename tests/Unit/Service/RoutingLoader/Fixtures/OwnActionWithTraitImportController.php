<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class OwnActionWithTraitImportController
{
    use AnnotatedHelperTrait;

    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/own-action-with-trait-import', methods: ['GET'])]
    public function show()
    {
    }
}
