<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as REST;

class AnnotationClassAsAttributeController
{
    #[REST\RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    public function show(): void
    {
    }
}
