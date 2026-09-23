<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\{
    Body, // the request body
    Query
};
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute, /* admin only */
    Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use /* the bundle's */ Paysera\Bundle\ApiBundle\Annotation\Validation;

class CommentedImportsController
{
    /**
     * @Query(parameterName="filter")
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @Validation(groups={"internal"})
     */
    public function find()
    {
    }
}
