<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CustomRestAnnotation as Query;

trait TraitWithAnotherQueryImport
{
    /**
     * @Query
     */
    public function find()
    {
    }
}
