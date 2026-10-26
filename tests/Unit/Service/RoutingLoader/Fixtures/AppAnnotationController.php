<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppPermission as Audit;

class AppAnnotationController
{
    /**
     * @Audit
     */
    #[Route('/app-annotation', methods: ['GET'])]
    public function show(): void
    {
    }
}
