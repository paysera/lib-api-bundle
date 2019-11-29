<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\PathAttributeResolver;

use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;

class MyObjectResolver implements PathAttributeResolverInterface
{
    public function resolveFromAttribute($input)
    {
        return (new MyObject())->setField1($input);
    }
}
