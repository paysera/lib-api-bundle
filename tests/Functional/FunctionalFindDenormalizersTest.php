<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\SimplePersistedEntity;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;

class FunctionalFindDenormalizersTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setUpContainer('basic');
        $this->setUpDatabase();
    }

    public function testRestRequestWithCustomPathConverter()
    {
        $response = $this->makeGetRequest('/persisted-entities/someField1');
        $this->assertEquals(404, $response->getStatusCode());

        $manager = $this->getEntityManager();
        $manager->persist((new PersistedEntity())->setId(42)->setSomeField('someField1'));
        $manager->persist((new SimplePersistedEntity())->setId(420));
        $manager->flush();

        $response = $this->makeGetRequest('/persisted-entities/someField1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('42', $response->getContent());

        $response = $this->makeGetRequest('/simple-persisted-entities/1');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->makeGetRequest('/simple-persisted-entities/420');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('420', $response->getContent());
    }
}
