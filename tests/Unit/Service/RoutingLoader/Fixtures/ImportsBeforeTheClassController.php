<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
/*
use Legacy\Annotation\RequiredPermissions;
*/

class ImportsBeforeTheClassController
{
    use Traits\Query;

    /**
     * @Query(parameterName="filter")
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function find()
    {
    }
}
