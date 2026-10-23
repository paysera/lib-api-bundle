<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class LowercaseFullyQualifiedDocblockController
{
    /** @\paysera\bundle\apibundle\annotation\validation */
    #[Route('/lowercase-fully-qualified', methods: ['GET'])]
    public function show(): void
    {
    }
}
