<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as REST;
use Paysera\Bundle\ApiBundle\Annotation\Validation as ApiValidation;

class AliasedImportController
{
    /**
     * @REST\Query(parameterName="filter")
     * @ApiValidation(groups={"internal"})
     * @\Paysera\Bundle\ApiBundle\Annotation\PathAttribute(parameterName="item", pathPartName="id")
     */
    public function find()
    {
    }
}
