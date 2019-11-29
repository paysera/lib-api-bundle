<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class MyObjectNormalizer implements ObjectDenormalizerInterface, TypeAwareInterface, NormalizerInterface
{
    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        $internal = $input->getObject('internal');
        $internalField = $internal !== null ? $internal->getString('field1') : null;

        return (new MyObject())
            ->setField1($input->getRequiredString('field1'))
            ->setInternalField1($internalField)
        ;
    }

    /**
     * @param MyObject $entity
     * @param NormalizationContext $normalizationContext
     * @return array
     */
    public function normalize($entity, NormalizationContext $normalizationContext)
    {
        return ['field1' => $entity->getField1()];
    }

    public function getType(): string
    {
        return MyObject::class;
    }
}
