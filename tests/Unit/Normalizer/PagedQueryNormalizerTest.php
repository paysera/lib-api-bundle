<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Normalizer;

use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Entity\PagedQuery;
use Paysera\Bundle\ApiBundle\Normalizer\Pagination\PagedQueryNormalizer;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Pagination\Entity\Doctrine\ConfiguredQuery;
use Paysera\Pagination\Entity\Pager;
use Paysera\Pagination\Entity\Result;
use Paysera\Pagination\Service\Doctrine\ResultProvider;
use stdClass;

class PagedQueryNormalizerTest extends MockeryTestCase
{
    public function testNormalizeWithTotalCountOnly()
    {
        $normalizationContext = Mockery::mock(NormalizationContext::class);

        $normalizationContext
            ->shouldReceive('isFieldIncluded')
            ->andReturnUsing(function (string $key) {
                return $key === '_metadata.total';
            })
        ;

        $resultProvider = Mockery::mock(ResultProvider::class);

        $configuredQuery = new ConfiguredQuery(Mockery::mock(QueryBuilder::class));
        $pager = Mockery::mock(Pager::class);

        $resultProvider
            ->shouldReceive('getTotalCountForQuery')
            ->withArgs(function ($givenQuery) use ($configuredQuery) {
                $this->assertEquals($configuredQuery, $givenQuery);
                return true;
            })
            ->once()
            ->andReturn(123)
        ;

        $finalResult = new stdClass();
        $normalizationContext
            ->shouldReceive('normalize')
            ->withArgs(function (Result $result) {
                $this->assertEquals(123, $result->getTotalCount());
                return true;
            })
            ->once()
            ->andReturn($finalResult)
        ;

        $normalizer = new PagedQueryNormalizer(
            $resultProvider,
            PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
            100
        );
        $normalizationResult = $normalizer->normalize(
            (new PagedQuery($configuredQuery, $pager)),
            $normalizationContext
        );

        $this->assertSame($finalResult, $normalizationResult);
    }

    /**
     * @dataProvider provideDataForNormalizeWithOptionalTotal
     *
     * @param ConfiguredQuery $expectedQuery
     * @param ConfiguredQuery $configuredQuery
     * @param array $included
     * @param array $explicitlyIncluded
     * @param string $defaultStrategy
     * @param string $queryStrategy
     * @param int|null $maximumOffset
     */
    public function testNormalize(
        ConfiguredQuery $expectedQuery,
        ConfiguredQuery $configuredQuery,
        array $included,
        array $explicitlyIncluded,
        string $defaultStrategy,
        string $queryStrategy,
        int $maximumOffset = null
    ) {
        $normalizationContext = Mockery::mock(NormalizationContext::class);

        $normalizationContext
            ->shouldReceive('isFieldIncluded')
            ->andReturnUsing(function (string $key) use ($included) {
                return in_array($key, $included, true);
            })
        ;
        $normalizationContext
            ->shouldReceive('isFieldExplicitlyIncluded')
            ->andReturnUsing(function (string $key) use ($explicitlyIncluded) {
                return in_array($key, $explicitlyIncluded, true);
            })
        ;

        $resultProvider = Mockery::mock(ResultProvider::class);

        $pager = Mockery::mock(Pager::class);
        $result = Mockery::mock(Result::class);

        $resultProvider
            ->shouldReceive('getResultForQuery')
            ->withArgs(function (ConfiguredQuery $givenQuery, $givenPager) use ($expectedQuery, $pager) {
                $this->assertEquals($expectedQuery->isTotalCountNeeded(), $givenQuery->isTotalCountNeeded());
                if ($expectedQuery->hasMaximumOffset()) {
                    $this->assertEquals($expectedQuery->getMaximumOffset(), $givenQuery->getMaximumOffset());
                }
                $this->assertSame($pager, $givenPager);
                return true;
            })
            ->once()
            ->andReturn($result)
        ;

        $finalResult = new stdClass();
        $normalizationContext
            ->shouldReceive('normalize')
            ->with($result, '')
            ->once()
            ->andReturn($finalResult)
        ;

        $normalizer = new PagedQueryNormalizer($resultProvider, $defaultStrategy, $maximumOffset);
        $normalizationResult = $normalizer->normalize(
            (new PagedQuery($configuredQuery, $pager))
                ->setTotalCountStrategy($queryStrategy),
            $normalizationContext
        );

        $this->assertSame($finalResult, $normalizationResult);
    }

    public function provideDataForNormalizeWithOptionalTotal()
    {
        $configuredQuery = new ConfiguredQuery(Mockery::mock(QueryBuilder::class));
        return [
            'No total count by default' => [
                (clone $configuredQuery)->setTotalCountNeeded(false),
                clone $configuredQuery,
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'No total count even if explicitly asked by configured query' => [
                (clone $configuredQuery)->setTotalCountNeeded(false),
                (clone $configuredQuery)->setTotalCountNeeded(true),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'Include total count on ALWAYS strategy by default' => [
                (clone $configuredQuery)->setTotalCountNeeded(true),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'Do not include total count if not asked even on ALWAYS strategy' => [
                (clone $configuredQuery)->setTotalCountNeeded(false),
                (clone $configuredQuery),
                ['items'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'Strategy in ConfiguredQuery wins over configured default strategy' => [
                (clone $configuredQuery)->setTotalCountNeeded(false),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                null,
            ],
            'Return total count on OPTIONAL strategy if explicitly asked for' => [
                (clone $configuredQuery)->setTotalCountNeeded(true),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                ['_metadata.total'],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'Do not return total count on OPTIONAL strategy even if explicitly asked for' => [
                (clone $configuredQuery)->setTotalCountNeeded(false),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                ['_metadata.total'],
                PagedQuery::TOTAL_COUNT_STRATEGY_NEVER,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                null,
            ],
            'Sets maximum offset' => [
                (clone $configuredQuery)->setMaximumOffset(100),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                100,
            ],
            'Ignores maximum offset on ALWAYS strategy configured explicitly' => [
                (clone $configuredQuery)->setTotalCountNeeded(true),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
                100,
            ],
            'Takes maximum offset into account on default always strategy' => [
                (clone $configuredQuery)->setTotalCountNeeded(true)->setMaximumOffset(100),
                (clone $configuredQuery),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_ALWAYS,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                100,
            ],
            'Does not overwrite bigger maximum offset' => [
                (clone $configuredQuery)->setMaximumOffset(200),
                (clone $configuredQuery)->setMaximumOffset(200),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                100,
            ],
            'Does not overwrite smaller maximum offset' => [
                (clone $configuredQuery)->setMaximumOffset(20),
                (clone $configuredQuery)->setMaximumOffset(20),
                ['items', '_metadata.total'],
                [],
                PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL,
                PagedQuery::TOTAL_COUNT_STRATEGY_DEFAULT,
                100,
            ],
        ];
    }
}
