<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader as Loader;
use Symfony\Component\Routing\Attribute\Route;

class SplitAliasAppAnnotationController
{
    /**
     * @Loader\Fixtures\
     * AppPermission
     */
    #[Route('/split-alias-app-annotation', methods: ['GET'])]
    public function show(): void
    {
    }
}
