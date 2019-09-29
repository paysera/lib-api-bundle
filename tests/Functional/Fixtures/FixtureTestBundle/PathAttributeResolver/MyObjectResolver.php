<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\PathAttributeResolver;

use Paysera\Bundle\RestBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;

class MyObjectResolver implements PathAttributeResolverInterface
{
    public function resolveFromAttribute($input)
    {
        return (new MyObject())->setField1($input);
    }
}
