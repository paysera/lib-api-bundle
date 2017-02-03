<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Filter;
use Paysera\Component\Serializer\Entity\Result;

class ResultProvider
{
    private $repository;

    public function __construct(BaseFilterAwareRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Filter $filter
     * @return Result
     */
    public function getResult(Filter $filter)
    {
        $result = (new Result($filter))
            ->setItems($this->repository->findByFilter($filter))
        ;

        if ($this->repository instanceof FilterAwareRepositoryInterface) {
            if ($result->calculateTotalCount(count($result->getItems())) === null) {
                $result->setTotalCount($this->repository->findCountByFilter($filter));
            }
        }

        if ($this->repository instanceof CursorFilterAwareRepositoryInterface) {
            $this->repository->populateResultCursors($result);
        }

        if ($filter->getOffset() !== null && $result->getTotalCount() !== null) {
            $result->setHasNext($filter->getOffset() + count($result->getItems()) < $result->getTotalCount());
        } elseif ($this->repository instanceof CursorFilterAwareRepositoryInterface) {
            $result->setHasNext($this->checkNextResult($filter, $result));
        }

        if ($filter->getOffset() !== null) {
            $result->setHasPrevious($filter->getOffset() > 0);
        } elseif ($this->repository instanceof CursorFilterAwareRepositoryInterface) {
            $result->setHasPrevious($this->checkPreviousResult($filter, $result));
        }

        return $result;
    }

    /**
     * @param Filter $filter
     * @param Result $result
     * @return bool
     */
    private function checkNextResult(Filter $filter, Result $result)
    {
        $nextFilter = clone $filter;
        $nextFilter->setLimit(1);
        $nextFilter->setBefore(null);
        $nextFilter->setAfter($result->getAfter());
        return count($this->repository->findByFilter($nextFilter)) > 0;
    }

    /**
     * @param Filter $filter
     * @param Result $result
     * @return bool
     */
    private function checkPreviousResult(Filter $filter, Result $result)
    {
        $previousFilter = clone $filter;
        $previousFilter->setLimit(1);
        $previousFilter->setAfter(null);
        $previousFilter->setBefore($result->getBefore());
        return count($this->repository->findByFilter($previousFilter)) > 0;
    }
}
