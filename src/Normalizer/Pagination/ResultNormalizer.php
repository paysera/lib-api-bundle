<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Normalizer\Pagination;

use Paysera\Pagination\Entity\Result;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;

class ResultNormalizer implements NormalizerInterface, TypeAwareInterface
{
    /**
     * @param Result $result
     * @param NormalizationContext $normalizationContext
     * @return array
     */
    public function normalize($result, NormalizationContext $normalizationContext)
    {
        return [
            'items' => $result->getItems(),
            '_metadata' => $this->mapMetadataFromEntity($result),
        ];
    }

    private function mapMetadataFromEntity(Result $result)
    {
        $data = [
            'total' => $result->getTotalCount(),
            'has_next' => $result->hasNext(),
            'has_previous' => $result->hasPrevious(),
        ];

        if ($result->getNextCursor() !== null) {
            $data['cursors']['after'] = $result->getNextCursor();
        }
        if ($result->getPreviousCursor() !== null) {
            $data['cursors']['before'] = $result->getPreviousCursor();
        }

        return $data;
    }

    public function getType(): string
    {
        return Result::class;
    }
}
