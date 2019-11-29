<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Entity\PagedQuery;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntityFilter;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Repository\PersistedEntityRepository;
use Paysera\Pagination\Entity\Pager;
use Symfony\Component\Routing\Annotation\Route;

class PagedQueryController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(path="/paged-query/simple", methods={"GET"})
     *
     * @Query(parameterName="pager")
     * @Query(parameterName="filter")
     *
     * @param Pager $pager
     * @param PersistedEntityFilter $filter
     * @return PagedQuery
     */
    public function findSimplePersistedEntities(Pager $pager, PersistedEntityFilter $filter)
    {
        /** @var PersistedEntityRepository $repository */
        $repository = $this->entityManager->getRepository(PersistedEntity::class);
        $configuredQuery = $repository->buildConfiguredQuery($filter);
        return new PagedQuery($configuredQuery, $pager);
    }
}
