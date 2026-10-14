<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class AnnotationClassConstantController
{
    /**
     * @RequiredPermissions(permissions={AuditAccess::ROLE})
     */
    #[Route('/annotation-class-constant', methods: ['GET'])]
    public function show()
    {
    }
}
