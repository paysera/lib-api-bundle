<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

/**
 * @IgnoreAnnotation("Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Target")
 */
class IgnoredQualifiedNameController
{
    /**
     * @Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Target(
     *     @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * )
     */
    public function show()
    {
    }
}
