<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class MyObjectCustomNormalizer implements ObjectDenormalizerInterface, NormalizerInterface
{
    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        return (new MyObject())->setField1($input->getString('field1_custom'));
    }

    /**
     * @param MyObject $entity
     * @param NormalizationContext $normalizationContext
     * @return array
     */
    public function normalize($entity, NormalizationContext $normalizationContext)
    {
        return ['field1_custom' => $entity->getField1()];
    }
}
