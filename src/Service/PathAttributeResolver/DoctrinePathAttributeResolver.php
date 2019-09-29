<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\PathAttributeResolver;

use Doctrine\Common\Persistence\ObjectRepository;

class DoctrinePathAttributeResolver implements PathAttributeResolverInterface
{
    private $repository;
    private $searchField;

    public function __construct(ObjectRepository $repository, string $searchField)
    {
        $this->repository = $repository;
        $this->searchField = $searchField;
    }

    public function resolveFromAttribute($attributeValue)
    {
        return $this->repository->findOneBy(
            [$this->searchField => $attributeValue]
        );
    }
}
