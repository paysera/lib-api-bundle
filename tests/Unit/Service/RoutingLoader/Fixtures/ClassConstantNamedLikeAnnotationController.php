<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\Validation;
use Symfony\Component\Routing\Attribute\Route;

class ClassConstantNamedLikeAnnotationController
{
    /**
     * @RequiredPermissions(permissions={Validation::ROLE})
     */
    #[Route('/class-constant-named-like-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
