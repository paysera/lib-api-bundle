<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;

trait TraitWithDocblockOptions
{
    /**
     * @Query(parameterName="filter")
     */
    public function fromTrait()
    {
    }
}
