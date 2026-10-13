<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @Query(parameterName="filter")
 */
class MethodAnnotationOnClassController
{
    #[Route('/method-annotation-on-class', methods: ['GET'])]
    public function show()
    {
    }
}
