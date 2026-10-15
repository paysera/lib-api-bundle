<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper as Helper;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @IgnoreAnnotation("Helper")
 */
class IgnoredNonAnnotationClassController
{
    /**
     * @Helper(@Rest\Query)
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/ignored-non-annotation-class', methods: ['GET'])]
    public function show()
    {
    }
}
