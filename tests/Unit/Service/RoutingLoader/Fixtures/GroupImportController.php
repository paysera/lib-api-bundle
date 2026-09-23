<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\{Body, RequiredPermissions as Permissions};

class GroupImportController
{
    /**
     * @Permissions(permissions={"ROLE_ADMIN"})
     * @Body(parameterName="item")
     */
    public function create()
    {
    }
}
