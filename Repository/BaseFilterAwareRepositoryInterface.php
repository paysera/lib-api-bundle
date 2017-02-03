<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Filter;
use Paysera\Component\Serializer\Exception\InvalidDataException;

/**
 * Abstract interface BaseFilterAwareRepositoryInterface
 * Don't use directly, implement one of extended interfaces
 */
interface BaseFilterAwareRepositoryInterface
{
    /**
     * @param Filter $filter
     *
     * @return array
     * @throws InvalidDataException on invalid filter values (after or before)
     */
    public function findByFilter($filter);
}
