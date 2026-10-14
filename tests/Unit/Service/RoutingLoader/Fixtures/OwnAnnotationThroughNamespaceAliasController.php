<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as App;
use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationThroughNamespaceAliasController
{
    /**
     * @App\AuditAccess
     */
    #[Route('/own-annotation-through-namespace-alias', methods: ['GET'])]
    public function show()
    {
    }
}
