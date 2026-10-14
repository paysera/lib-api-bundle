<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Symfony\Component\Routing\Attribute\Route;

class SameNamespaceReferencesDocblockController
{
    /**
     * @RequiredPermissions(permissions={Support\Roles::ADMIN})
     * @ResponseNormalization(normalizationType=NoBundleAnnotationController::class)
     */
    #[Route('/same-namespace-references', methods: ['GET'])]
    public function show()
    {
    }
}
