<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\SimplePersistedEntity;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;

class FunctionalFindDenormalizersTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContainer('basic');
        $this->setUpDatabase();
    }

    /**
     * @dataProvider routesProvider
     */
    public function testRestRequestWithCustomPathConverter(string $routes, string $pathPrefix)
    {
        $this->skipUnlessRoutesAreLoaded($routes);

        $response = $this->makeGetRequest($pathPrefix . '/persisted-entities/someField1');
        $this->assertEquals(404, $response->getStatusCode());

        $manager = $this->getEntityManager();
        $manager->persist((new PersistedEntity())->setId(42)->setSomeField('someField1'));
        $manager->persist((new SimplePersistedEntity())->setId(420));
        $manager->flush();

        $response = $this->makeGetRequest($pathPrefix . '/persisted-entities/someField1');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('42', $response->getContent());

        $response = $this->makeGetRequest($pathPrefix . '/simple-persisted-entities/1');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->makeGetRequest($pathPrefix . '/simple-persisted-entities/420');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('420', $response->getContent());
    }

    public static function routesProvider(): array
    {
        return [
            'annotated' => ['annotated', ''],
            'attributed' => ['attributed', '/attributed'],
        ];
    }
}
