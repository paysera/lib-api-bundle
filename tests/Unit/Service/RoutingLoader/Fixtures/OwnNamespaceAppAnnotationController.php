<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class OwnNamespaceAppAnnotationController
{
    /**
     * @AppPermission
     */
    #[Route('/own-namespace-app-annotation', methods: ['GET'])]
    public function show(): void
    {
    }
}
