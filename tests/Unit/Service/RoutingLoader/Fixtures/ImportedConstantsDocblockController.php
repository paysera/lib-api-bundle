<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\Roles;
use Symfony\Component\Routing\Attribute\Route;

class ImportedConstantsDocblockController
{
    /**
     * @RequiredPermissions(permissions={Roles::ADMIN})
     * @ResponseNormalization(normalizationType=RestRequestOptions::class)
     */
    #[Route('/imported-constants', methods: ['GET'])]
    public function show()
    {
    }
}
