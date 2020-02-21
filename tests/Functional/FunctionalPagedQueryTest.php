<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\PersistedEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalPagedQueryTest extends FunctionalTestCase
{
    /**
     * @dataProvider requestWithPagedQueryProvider
     * @param Response $expectedResponse
     * @param Request $request
     * @param string $testCase
     */
    public function testRequestWithPagedQuery(Response $expectedResponse, Request $request, string $testCase = 'basic')
    {
        $this->setUpFor($testCase);
        $response = $this->handleRequest($request);
        $this->assertEquals(
            $expectedResponse->getContent(),
            $response->getContent(),
            'expected correct content'
        );
        $this->assertEquals(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode(),
            'expected correct status code'
        );
    }

    public function requestWithPagedQueryProvider()
    {
        $firstResponse = (new JsonResponse([
            'items' => [
                ['id' => 100, 'some_field' => 'field100'],
                ['id' => 99, 'some_field' => 'field99'],
                ['id' => 98, 'some_field' => 'field98'],
            ],
            '_metadata' => [
                'total' => null,
                'has_next' => true,
                'has_previous' => false,
                'cursors' => [
                    'after' => '"98"',
                    'before' => '"100"',
                ],
            ],
        ]))->setEncodingOptions(0);

        return [
            'testPagedQuery' => [
                $firstResponse,
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3'
                ),
            ],
            'testPagedQuery with after' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 97, 'some_field' => 'field97'],
                        ['id' => 96, 'some_field' => 'field96'],
                        ['id' => 95, 'some_field' => 'field95'],
                    ],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => true,
                        'has_previous' => true,
                        'cursors' => [
                            'after' => '"95"',
                            'before' => '"97"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&after="98"'
                ),
            ],
            'testPagedQuery with before' => [
                $firstResponse,
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&before="97"'
                ),
            ],
            'testPagedQuery with before with no content' => [
                (new JsonResponse([
                    'items' => [],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => true,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '="100"',
                            'before' => '"100"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&before="100"'
                ),
            ],
            'testPagedQuery with first after cursor' => [
                $firstResponse,
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&after=' . urlencode('="100"')
                ),
            ],
            'testPagedQuery with explicit total count' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 100, 'some_field' => 'field100'],
                        ['id' => 99, 'some_field' => 'field99'],
                        ['id' => 98, 'some_field' => 'field98'],
                    ],
                    '_metadata' => [
                        'total' => 100,
                        'has_next' => true,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '"98"',
                            'before' => '"100"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&fields=*,_metadata.total'
                ),
            ],
            'testPagedQuery with explicit offset' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 90, 'some_field' => 'field90'],
                        ['id' => 89, 'some_field' => 'field89'],
                        ['id' => 88, 'some_field' => 'field88'],
                    ],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => true,
                        'has_previous' => true,
                        'cursors' => [
                            'after' => '"88"',
                            'before' => '"90"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=3&offset=10'
                ),
            ],
            'testPagedQuery with filter' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 66, 'some_field' => 'field66'],
                    ],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => false,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '"66"',
                            'before' => '"66"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?some_field=field66'
                ),
            ],
            'testPagedQuery with different order' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 1, 'some_field' => 'field1'],
                    ],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => true,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '"field1","1"',
                            'before' => '"field1","1"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?sort=some_field&limit=1'
                ),
            ],
            'testPagedQuery with double order' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 1, 'some_field' => 'field1'],
                    ],
                    '_metadata' => [
                        'total' => null,
                        'has_next' => true,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '"1","field1"',
                            'before' => '"1","field1"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?sort=id,-some_field&limit=1'
                ),
            ],
            'testPagedQuery with cursor from different sorting' => [
                new JsonResponse([
                    'error' => 'invalid_cursor',
                    'error_description' => 'Provided cursor is invalid',
                    'error_uri' => null,
                    'error_properties' => null,
                    'error_data' => null,
                    'errors' => null,
                ], 400),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=1&after="1","field1"'
                ),
            ],
            'testPagedQuery with too large offset' => [
                new JsonResponse([
                    'error' => 'offset_too_large',
                    'error_description' => 'Given offset (10) is bigger than maximum allowed (5). Please use cursor-based navigation',
                    'error_uri' => null,
                    'error_properties' => null,
                    'error_data' => null,
                    'errors' => null,
                ], 400),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?offset=10'
                ),
                'custom_pagination',    // maximum_offset: 5
            ],
            'testPagedQuery with too large limit' => [
                new JsonResponse([
                    'error' => 'invalid_parameters',
                    'error_description' => 'limit cannot exceed 2',
                    'error_uri' => null,
                    'error_properties' => null,
                    'error_data' => null,
                    'errors' => null,
                ], 400),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?limit=10'
                ),
                'custom_pagination',    // maximum_limit: 2
            ],
            'testPagedQuery with `always` strategy and custom default limit' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 100, 'some_field' => 'field100'],
                    ],
                    '_metadata' => [
                        'total' => 100,
                        'has_next' => true,
                        'has_previous' => false,
                        'cursors' => [
                            'after' => '"100"',
                            'before' => '"100"',
                        ],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple'
                ),
                'custom_pagination',    // default_limit: 1
            ],
            'testPagedQuery with excluded fields' => [
                (new JsonResponse([
                    'items' => [
                        ['id' => 100, 'some_field' => 'field100'],
                    ],
                ]))->setEncodingOptions(0),
                $this->createRequest(
                    'GET',
                    '/paged-query/simple?fields=items'
                ),
                'custom_pagination',
            ],
        ];
    }

    private function setUpFor(string $testCase)
    {
        $this->setUpContainer($testCase);
        $this->setUpDatabase();
        $this->persistEntities();
    }

    private function persistEntities()
    {
        $manager = $this->getEntityManager();
        for ($id = 1; $id <= 100; $id++) {
            $manager->persist((new PersistedEntity())->setId($id)->setSomeField('field' . $id));
        }
        $manager->flush();
    }
}
