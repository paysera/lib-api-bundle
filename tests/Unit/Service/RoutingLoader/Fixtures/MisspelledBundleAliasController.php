<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class MisspelledBundleAliasController
{
    /**
     * @Rest\RequiredPermission(permissions={"ROLE_ADMIN"})
     */
    #[Route('/misspelled-bundle-alias', methods: ['GET'])]
    public function show()
    {
    }
}
