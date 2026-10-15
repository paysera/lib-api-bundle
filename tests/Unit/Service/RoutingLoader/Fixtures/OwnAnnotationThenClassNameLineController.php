<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationThenClassNameLineController
{
    /**
     * @AuditAccess
     * \App\Query objects are listed here.
     */
    #[Route('/own-annotation-then-class-name-line', methods: ['GET'])]
    public function show()
    {
    }
}
