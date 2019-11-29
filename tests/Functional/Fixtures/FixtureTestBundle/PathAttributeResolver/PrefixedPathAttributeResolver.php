<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\PathAttributeResolver;

use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;

class PrefixedPathAttributeResolver implements PathAttributeResolverInterface
{
    public function resolveFromAttribute($attributeValue)
    {
        return 'prefixed_' . $attributeValue;
    }
}
