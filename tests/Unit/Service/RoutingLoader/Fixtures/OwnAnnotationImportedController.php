<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationImportedController
{
    /**
     * @AuditAccess
     */
    #[Route('/own-annotation-imported', methods: ['GET'])]
    public function show()
    {
    }
}
