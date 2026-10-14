<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary\Query as LibraryQuery;
use Symfony\Component\Routing\Attribute\Route;

class ForeignAnnotationTagAndClassNameController
{
    /**
     * @LibraryQuery(parameterName={"filter"})
     * @ResponseNormalization(normalizationType=LibraryQuery::class)
     */
    #[Route('/foreign-annotation-tag-and-class-name', methods: ['GET'])]
    public function show()
    {
    }
}
