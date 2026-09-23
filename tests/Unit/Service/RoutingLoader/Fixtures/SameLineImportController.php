<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures; use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

class SameLineImportController
{
    /**
     * @Paysera\Bundle\ApiBundle\Annotation\Body(parameterName="item")
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function create()
    {
    }
}
