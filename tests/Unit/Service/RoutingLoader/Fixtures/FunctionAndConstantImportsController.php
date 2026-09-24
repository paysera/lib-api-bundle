<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use function Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\requiredPermissions;
use const Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\RequiredPermissions;

class FunctionAndConstantImportsController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function show()
    {
    }
}
