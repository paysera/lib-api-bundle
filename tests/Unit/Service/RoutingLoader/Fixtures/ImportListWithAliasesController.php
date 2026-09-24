<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use \Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\Body as B, Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions as P,
    Paysera\Bundle\ApiBundle\Annotation\Validation;

class ImportListWithAliasesController
{
    /**
     * @P(permissions={"ROLE_ADMIN"})
     * @B(parameterName="item")
     * @Validation(groups={"internal"})
     * @Query(parameterName="filter")
     */
    public function create()
    {
    }
}
