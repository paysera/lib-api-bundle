<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Annotation\Validation;

class ArgumentBoundariesController
{
    /**
     * @ResponseNormalization
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function afterAnAnnotationWithoutArguments()
    {
    }

    /**
     * @Query(parameterName=")")
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function afterAParenthesisInAString()
    {
    }

    /**
     * @Query (parameterName="filter", validation= @Validation(groups={"filter"}))
     */
    public function argumentsAfterASpace()
    {
    }

    /**
     * @ResponseNormalization: plain
     */
    public function colonAfterAName()
    {
    }

    /**
     * @ResponseNormalization-1
     */
    public function nameFollowedByANegativeNumber()
    {
    }
}
