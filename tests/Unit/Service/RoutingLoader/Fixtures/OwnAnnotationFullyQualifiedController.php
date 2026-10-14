<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationFullyQualifiedController
{
    /**
     * @\Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess
     */
    #[Route('/own-annotation-fully-qualified', methods: ['GET'])]
    public function show()
    {
    }
}
