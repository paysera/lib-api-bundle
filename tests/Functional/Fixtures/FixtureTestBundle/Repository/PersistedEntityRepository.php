<?php

declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntityFilter;
use Paysera\Pagination\Entity\Doctrine\ConfiguredQuery;
use Paysera\Pagination\Entity\OrderingConfiguration;

class PersistedEntityRepository extends EntityRepository
{
    public function buildConfiguredQuery(PersistedEntityFilter $filter)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if ($filter->getSomeField() !== null) {
            $queryBuilder
                ->andWhere('e.someField = :someField')
                ->setParameter('someField', $filter->getSomeField())
            ;
        }

        return (new ConfiguredQuery($queryBuilder))
            ->addOrderingConfiguration(
                'some_field',
                new OrderingConfiguration('e.someField', 'someField')
            )
            ->addOrderingConfiguration(
                'id',
                new OrderingConfiguration('e.id', 'id')
            )
        ;
    }
}
