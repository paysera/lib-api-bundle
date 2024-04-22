<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\ApiBundle\Annotation\PathAttribute;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\SimplePersistedEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PersistedEntityController
{
    /**
     * @Route(path="/persisted-entities/{identifier}", methods={"GET"})
     *
     * @PathAttribute(parameterName="entity", pathPartName="identifier")
     */
    public function findPersistedEntity(PersistedEntity $entity): Response
    {
        return new Response((string)$entity->getId());
    }

    /**
     * @Route(path="/simple-persisted-entities/{identifier}", methods={"GET"})
     *
     * @PathAttribute(parameterName="entity", pathPartName="identifier")
     */
    public function findSimplePersistedEntity(SimplePersistedEntity $entity): Response
    {
        return new Response((string)$entity->getId());
    }
}
