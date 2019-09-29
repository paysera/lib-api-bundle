<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\PathAttributeResolver;

use Paysera\Bundle\RestBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;

class NullResolver implements PathAttributeResolverInterface
{
    public function resolveFromAttribute($attributeValue)
    {
        return null;
    }
}
