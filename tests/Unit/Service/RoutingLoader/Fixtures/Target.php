<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

/**
 * An application's own annotation class whose short name is one of the tag names Doctrine's reader ignores.
 *
 * @Annotation
 */
class Target
{
    /**
     * @var mixed
     */
    public $value;
}
