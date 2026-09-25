<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Body,
    Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

class CommaImportController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @Body(parameterName="item")
     */
    public function create()
    {
    }
}
