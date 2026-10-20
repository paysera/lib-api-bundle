<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class MissingParentTagController
{
    /**
     * @ClassWithMissingParent
     */
    #[Route('/missing-parent-tag', methods: ['GET'])]
    public function show(): void
    {
    }
}
