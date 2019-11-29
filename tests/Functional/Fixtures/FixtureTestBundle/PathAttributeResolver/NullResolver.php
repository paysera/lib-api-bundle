<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\PathAttributeResolver;

use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;

class NullResolver implements PathAttributeResolverInterface
{
    public function resolveFromAttribute($attributeValue)
    {
        return null;
    }
}
