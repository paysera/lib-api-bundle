<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Filter;
use Paysera\Component\Serializer\Entity\Result;

class ResultProvider
{
    private $repository;

    public function __construct(FilterAwareRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getResult(Filter $filter)
    {
        $items = $this->repository->findByFilter($filter);
        $result = new Result($filter);
        $result->setItems($items);
        if ($result->calculateTotalCount(count($items)) === null) {
            $result->setTotalCount($this->repository->findCountByFilter($filter));
        }
        return $result;
    }
} 
