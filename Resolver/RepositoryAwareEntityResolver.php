<?php

namespace Paysera\Bundle\RestBundle\Resolver;

use Doctrine\Common\Persistence\ObjectRepository;

class RepositoryAwareEntityResolver implements EntityResolverInterface
{
    /**
     * @var ObjectRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $searchField;

    public function __construct(ObjectRepository $repository, $searchField)
    {
        $this->repository = $repository;
        $this->searchField = $searchField;
    }

    public function resolveFrom($value)
    {
        return $this->repository->findOneBy(
            array($this->searchField => $value)
        );
    }
}
