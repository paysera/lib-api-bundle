<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Validation;

class QualifiedNameController
{
    /**
     * @Paysera\Bundle\ApiBundle\Annotation\Query(parameterName="filter", validation= @Validation(groups={"filter"}))
     */
    public function withoutTheLeadingBackslash()
    {
    }

    /**
     * @\Paysera\Bundle\ApiBundle\Annotation\Query(parameterName="filter", validation= @Validation(groups={"filter"}))
     */
    public function withTheLeadingBackslash()
    {
    }

    /**
     * @\\Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function withTwoLeadingBackslashes()
    {
    }
}
