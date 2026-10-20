<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppPermission as audit;
use Symfony\Component\Routing\Attribute\Route;

class LowercaseAliasAppAnnotationController
{
    /**
     * @audit
     */
    #[Route('/lowercase-alias-app-annotation', methods: ['GET'])]
    public function show(): void
    {
    }
}
