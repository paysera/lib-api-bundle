<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\MixedTypeDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;

class PrefixedDenormalizer implements MixedTypeDenormalizerInterface, TypeAwareInterface
{
    public function denormalize($input, DenormalizationContext $context)
    {
        return 'prefixed_' . $input;
    }

    public function getType(): string
    {
        return 'prefixed';
    }
}
