<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary\Query as LibraryQuery;
use Symfony\Component\Routing\Attribute\Route;

class ForeignAnnotationClassConstantController
{
    /**
     * @RequiredPermissions(permissions={LibraryQuery::ROLE})
     */
    #[Route('/foreign-annotation-class-constant', methods: ['GET'])]
    public function show()
    {
    }
}
