<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;

class NonAttributeBundleClassController
{
    #[RestRequestOptions]
    public function show(): void
    {
    }
}
