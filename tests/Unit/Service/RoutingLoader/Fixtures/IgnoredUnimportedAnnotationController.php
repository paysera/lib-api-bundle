<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("RequiredPermissions")
 */
class IgnoredUnimportedAnnotationController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/ignored-unimported-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
