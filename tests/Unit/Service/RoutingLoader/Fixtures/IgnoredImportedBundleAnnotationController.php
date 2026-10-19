<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("RequiredPermissions")
 */
class IgnoredImportedBundleAnnotationController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/ignored-imported-bundle-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
