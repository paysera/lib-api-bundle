<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

/**
 * @RequiredPermissions(permissions={"ROLE_CLASS"})
 */
class DirectImportController
{
    /**
     * {@inheritdoc}
     *
     * @Body(parameterName="item")
     * @RequiredPermissions(permissions={"ROLE_METHOD"})
     *
     * @param mixed $item
     */
    public function create($item)
    {
    }
}
