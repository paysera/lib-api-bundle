<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;

abstract class ParentWithDocblockOptions
{
    /**
     * @ResponseNormalization()
     */
    public function inherited()
    {
    }
}
