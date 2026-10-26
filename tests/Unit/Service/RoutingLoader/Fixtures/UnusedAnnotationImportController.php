<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class UnusedAnnotationImportController
{
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[Route('/unused-annotation-import', methods: ['GET'])]
    public function show(): void
    {
    }
}
