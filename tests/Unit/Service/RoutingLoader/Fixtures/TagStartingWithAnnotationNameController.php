<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Query;
use Symfony\Component\Routing\Attribute\Route;

class TagStartingWithAnnotationNameController
{
    /**
     * @QueryParam(name="filter")
     * @RequiredPermissions(permissions={Query\Roles::ADMIN})
     */
    #[Route('/tag-starting-with-annotation-name', methods: ['GET'])]
    public function show()
    {
    }
}
