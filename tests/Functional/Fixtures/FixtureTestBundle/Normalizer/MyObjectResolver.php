<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\MixedTypeDenormalizerInterface;

class MyObjectResolver implements MixedTypeDenormalizerInterface
{
    public function denormalize($input, DenormalizationContext $context)
    {
        return (new MyObject())->setField1($input);
    }
}
