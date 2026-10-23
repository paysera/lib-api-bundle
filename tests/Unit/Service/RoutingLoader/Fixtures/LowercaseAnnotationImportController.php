<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use paysera\bundle\apibundle\annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class LowercaseAnnotationImportController
{
    #[Route('/lowercase-annotation-import', methods: ['GET'])]
    public function show(): void
    {
    }
}
