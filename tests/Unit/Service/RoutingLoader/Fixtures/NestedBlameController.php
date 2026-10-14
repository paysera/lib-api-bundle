<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Body;
use Symfony\Component\Routing\Attribute\Route;

class NestedBlameController
{
    /**
     * @Body\AuditAccess
     * @Query(parameterName="filter", validation=@Validation(enabled=false))
     */
    #[Route('/nested-blame', methods: ['GET'])]
    public function show()
    {
    }
}
