<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\Validation;

class NotTopLevelAnnotationsController
{
    /**
     * Mail ops@RequiredPermissions.example; {@RequiredPermissions} is an inline tag, not an annotation.
     *
     * @Query(parameterName="filter", validation=@Validation(groups={"filter"}))
     */
    public function find()
    {
    }
}
