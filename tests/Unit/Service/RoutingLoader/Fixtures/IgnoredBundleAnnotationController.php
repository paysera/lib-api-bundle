<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("RequiredPermissions")
 */
class IgnoredBundleAnnotationController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/ignored-bundle-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
