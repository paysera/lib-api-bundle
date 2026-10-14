<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary as Lib;
use Symfony\Component\Routing\Attribute\Route;

class NamespaceAliasForTagAndReferenceController
{
    /**
     * @Lib\Query(parameterName={"filter"})
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @ResponseNormalization(normalizationType=Lib\SameNamespaceAnnotationController::class)
     */
    #[Route('/namespace-alias-for-tag-and-reference', methods: ['GET'])]
    public function show()
    {
    }
}
