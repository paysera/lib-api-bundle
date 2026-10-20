<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class AppAnnotationFullyQualifiedController
{
    /**
     * @\Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppPermission
     */
    #[Route('/app-annotation-fully-qualified', methods: ['GET'])]
    public function show(): void
    {
    }
}
