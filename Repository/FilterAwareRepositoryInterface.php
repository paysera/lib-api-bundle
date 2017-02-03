<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Filter;

interface FilterAwareRepositoryInterface extends BaseFilterAwareRepositoryInterface
{
    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function findCountByFilter($filter);
}
