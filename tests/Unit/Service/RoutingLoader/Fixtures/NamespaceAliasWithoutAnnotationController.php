<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Rest;
use Symfony\Component\Routing\Attribute\Route;

class NamespaceAliasWithoutAnnotationController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/namespace-alias-without-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
