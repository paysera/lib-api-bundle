<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service\Validation;

use Paysera\Bundle\RestBundle\Service\Validation\CamelCaseToSnakeCaseConverter;
use PHPUnit\Framework\TestCase;

class CamelCaseToSnakeCaseConverterTest extends TestCase
{

    /**
     * @dataProvider provideDataForConvert
     *
     * @param string $expected
     * @param string $path
     */
    public function testConvert(string $expected, string $path)
    {
        $this->assertSame($expected, (new CamelCaseToSnakeCaseConverter())->convert($path));
    }

    public function provideDataForConvert()
    {
        return [
            ['string', 'string'],
            ['snake_case', 'snake_case'],
            ['camel_case', 'camelCase'],
            ['title_case', 'TitleCase'],
            ['title_case.word', 'TitleCase.word'],
        ];
    }
}
