<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Symfony\Component\Routing\Attribute\Route;

class NestedUnimportedAliasController
{
    /**
     * @Query(parameterName="filter", validation=@Rest\Validation(enabled=false))
     */
    #[Route('/nested-unimported-alias', methods: ['GET'])]
    public function show()
    {
    }
}
