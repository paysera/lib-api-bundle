<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Normalizer\Pagination;

use Paysera\Pagination\Entity\OrderingPair;
use Paysera\Pagination\Entity\Pager;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\Exception\InvalidDataException;
use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;
use Paysera\Component\ObjectWrapper\Exception\InvalidItemException;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class PagerDenormalizer implements ObjectDenormalizerInterface, TypeAwareInterface
{
    private $defaultLimit;
    private $maxLimit;

    public function __construct(int $defaultLimit, int $maxLimit)
    {
        $this->defaultLimit = $defaultLimit;
        $this->maxLimit = $maxLimit;
    }

    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        $limit = isset($input['limit']) ? $this->getPositiveInt($input, 'limit') : $this->defaultLimit;
        if ($limit > $this->maxLimit) {
            throw new InvalidItemException(
                'limit',
                sprintf('limit cannot exceed %s', $this->maxLimit)
            );
        }

        $offset = isset($input['offset']) ? $this->getPositiveInt($input, 'offset') : null;
        $after = $input->getString('after');
        $before = $input->getString('before');

        if (
            ($before !== null && $after !== null)
            || ($before !== null && $offset !== null)
            || ($after !== null && $offset !== null)
        ) {
            throw new InvalidDataException('Only one of offset, before and after can be specified');
        }

        $orderingPairs = isset($input['sort']) ? $this->parseOrderingPairs($input->getString('sort')) : [];

        return (new Pager())
            ->setOrderingPairs($orderingPairs)
            ->setOffset($offset)
            ->setLimit($limit)
            ->setBefore($before)
            ->setAfter($after)
        ;
    }

    private function parseOrderingPairs(string $ordering): array
    {
        $orderingPairs = [];
        $orderingFields = explode(',', $ordering);
        foreach ($orderingFields as $field) {
            $orderAsc = true;
            if (mb_substr($field, 0, 1) === '-') {
                $field = mb_substr($field, 1);
                $orderAsc = false;
            }

            $orderingPairs[] = new OrderingPair($field, $orderAsc);
        }

        return $orderingPairs;
    }

    private function getPositiveInt(ObjectWrapper $input, string $key): int
    {
        $filtered = filter_var($input->getRequiredString($key), FILTER_VALIDATE_INT, ['options' => [
            'min_range' => 0,
        ]]);

        if ($filtered === false) {
            throw new InvalidItemException($key, sprintf('%s must be positive integer', $key));
        }

        return $filtered;
    }

    public function getType(): string
    {
        return Pager::class;
    }
}
