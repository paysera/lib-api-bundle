<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;

class ControllerOverridingTheTraitMethod
{
    use TraitWithAnotherQueryImport;

    /**
     * @Query(parameterName="filter")
     */
    public function find()
    {
    }
}
