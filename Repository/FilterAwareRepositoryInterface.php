<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Filter;

interface FilterAwareRepositoryInterface
{
    /**
     * @param Filter $filter
     *
     * @return array
     */
    public function findByFilter($filter);

    /**
     * @param Filter $filter
     *
     * @return int
     */
    public function findCountByFilter($filter);
} 
