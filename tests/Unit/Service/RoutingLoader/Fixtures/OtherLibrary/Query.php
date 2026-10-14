<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Query
{
    public const ROLE = 'ROLE_ADMIN';

    /**
     * @var string
     */
    public $parameterName;
}
