<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class NestedUnimportedAnnotationController
{
    /**
     * @Rest\Query(parameterName="filter", validation=@Validation(enabled=false))
     */
    #[Route('/nested-unimported-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
