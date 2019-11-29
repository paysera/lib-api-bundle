<?php

declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;

class PersistedEntityNormalizer implements NormalizerInterface, TypeAwareInterface
{
    /**
     * @param PersistedEntity $persistedEntity
     * @param NormalizationContext $normalizationContext
     * @return array
     */
    public function normalize($persistedEntity, NormalizationContext $normalizationContext)
    {
        return [
            'id' => $persistedEntity->getId(),
            'some_field' => $persistedEntity->getSomeField(),
        ];
    }

    public function getType(): string
    {
        return PersistedEntity::class;
    }
}
