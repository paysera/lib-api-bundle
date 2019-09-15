<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Normalizer\Pagination;

use Exception;
use Paysera\Bundle\RestBundle\Normalizer\Pagination\PagerDenormalizer;
use Paysera\Bundle\RestBundle\Tests\Unit\Normalizer\DenormalizerTestCase;
use Paysera\Component\Normalization\Exception\InvalidDataException;
use Paysera\Component\ObjectWrapper\Exception\InvalidItemException;
use Paysera\Pagination\Entity\OrderingPair;
use Paysera\Pagination\Entity\Pager;

class PagerDenormalizerTest extends DenormalizerTestCase
{

    /**
     * @dataProvider provideDataForTestDenormalize
     *
     * @param Pager|Exception $expected
     * @param string $queryString
     * @param int $defaultMaxLimit
     */
    public function testDenormalize($expected, string $queryString, int $defaultMaxLimit = 200)
    {
        parse_str($queryString, $data);

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $this->assertEquals($expected, $this->callDenormalize(
            new PagerDenormalizer($defaultMaxLimit),
            $data
        ));
    }

    public function provideDataForTestDenormalize()
    {
        return [
            [
                new Pager(),
                '',
            ],
            [
                (new Pager())
                    ->setLimit(100)
                ,
                'limit=100',
            ],
            [
                new InvalidItemException('limit', 'limit cannot exceed 200'),
                'limit=300',
            ],
            [
                (new Pager())
                    ->setLimit(300)
                ,
                'limit=300',
                300,
            ],
            [
                new InvalidItemException('limit', 'limit must be positive integer'),
                'limit=1abc',
            ],
            [
                new InvalidItemException('limit', 'limit must be positive integer'),
                'limit=-3',
            ],
            [
                new InvalidItemException('limit', 'Expected string but got array for key "limit"'),
                'limit[]=10',
            ],
            [
                (new Pager())
                    ->setOffset(100)
                ,
                'offset=100',
            ],
            [
                new InvalidItemException('offset', 'offset must be positive integer'),
                'offset=1abc',
            ],
            [
                new InvalidItemException('offset', 'offset must be positive integer'),
                'offset=-3',
            ],
            [
                new InvalidItemException('offset', 'Expected string but got array for key "offset"'),
                'offset[]=10',
            ],
            [
                (new Pager())
                    ->setAfter('abc')
                ,
                'after=abc',
            ],
            [
                new InvalidItemException('after', 'Expected string but got array for key "after"'),
                'after[]=abc',
            ],
            [
                (new Pager())
                    ->setBefore('abc')
                ,
                'before=abc',
            ],
            [
                new InvalidItemException('before', 'Expected string but got array for key "before"'),
                'before[]=abc',
            ],
            [
                new InvalidDataException('Only one of offset, before and after can be specified'),
                'offset=10&after=abc',
            ],
            [
                new InvalidDataException('Only one of offset, before and after can be specified'),
                'offset=10&before=abc',
            ],
            [
                new InvalidDataException('Only one of offset, before and after can be specified'),
                'after=as&before=abc',
            ],
            [
                new InvalidDataException('Only one of offset, before and after can be specified'),
                'after=as&before=abc&offset=213',
            ],
            [
                (new Pager())
                    ->addOrderBy(new OrderingPair('a', true))
                ,
                'sort=a',
            ],
            [
                (new Pager())
                    ->addOrderBy(new OrderingPair('a', true))
                    ->addOrderBy(new OrderingPair('b', true))
                ,
                'sort=a,b',
            ],
            [
                (new Pager())
                    ->addOrderBy(new OrderingPair('a', false))
                    ->addOrderBy(new OrderingPair('b', true))
                    ->addOrderBy(new OrderingPair('c', false))
                ,
                'sort=-a,b,-c',
            ],
            [
                new InvalidItemException('sort', 'Expected string but got array for key "sort"'),
                'sort[]=a&sort[]=b',
            ],
            [
                (new Pager())
                    ->setAfter('1')
                    ->setLimit(100)
                    ->addOrderBy(new OrderingPair('a', true))
                ,
                'after=1&limit=100&sort=a',
            ],
        ];
    }
}
