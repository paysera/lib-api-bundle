<?php

declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntityFilter;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class PersistedEntityFilterDenormalizer implements ObjectDenormalizerInterface, TypeAwareInterface
{
    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        return (new PersistedEntityFilter())
            ->setSomeField($input->getString('some_field'))
        ;
    }

    public function getType(): string
    {
        return PersistedEntityFilter::class;
    }
}
