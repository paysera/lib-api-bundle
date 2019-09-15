<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\MixedTypeDenormalizerInterface;

class NullResolver implements MixedTypeDenormalizerInterface
{
    public function denormalize($input, DenormalizationContext $context)
    {
        return null;
    }
}
