<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class RelativeForeignAnnotationController
{
    /**
     * @OtherLibrary\Query(parameterName="filter")
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/relative-foreign-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
