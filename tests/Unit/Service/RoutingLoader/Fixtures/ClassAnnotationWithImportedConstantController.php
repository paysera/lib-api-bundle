<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\Roles;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={Roles::ADMIN})
 */
class ClassAnnotationWithImportedConstantController
{
    /**
     * Shows the thing.
     */
    #[Route('/class-annotation-with-imported-constant', methods: ['GET'])]
    public function show()
    {
    }
}
