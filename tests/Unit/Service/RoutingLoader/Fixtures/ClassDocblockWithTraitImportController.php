<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @RequiredPermissions(permissions={"ROLE_ADMIN"})
 */
class ClassDocblockWithTraitImportController
{
    use AnnotatedHelperTrait;

    #[Route('/class-docblock-with-trait-import', methods: ['GET'])]
    public function show()
    {
    }
}
