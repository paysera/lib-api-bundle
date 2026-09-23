<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Entity;

use Paysera\Bundle\ApiBundle\Entity\Error;
use Paysera\Bundle\ApiBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Reading an option that was never set fails with a message that says what to check first.
 */
class UnsetOptionsTest extends TestCase
{
    /**
     * @dataProvider unsetOptionDataProvider
     */
    public function testReadingAnUnsetOptionFails($options, string $getter, string $expectedMessage)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $options->$getter();
    }

    public static function unsetOptionDataProvider(): array
    {
        return [
            [new PathAttributeResolverOptions(), 'getParameterName', 'parameterName was not set'],
            [new PathAttributeResolverOptions(), 'getPathPartName', 'pathPartName was not set'],
            [new PathAttributeResolverOptions(), 'getPathAttributeResolverType', 'pathAttributeResolverType was not set'],
            [new QueryResolverOptions(), 'getParameterName', 'parameterName was not set'],
            [new QueryResolverOptions(), 'getDenormalizationType', 'denormalizationType was not set'],
            [
                new QueryResolverOptions(),
                'getValidationOptions',
                'No validationOptions available, call isValidationNeeded beforehand',
            ],
            [
                new RestRequestOptions(),
                'getBodyDenormalizationType',
                'No bodyDenormalizationType available, call hasBodyDenormalization beforehand',
            ],
            [
                new RestRequestOptions(),
                'getBodyParameterName',
                'No bodyParameterName available, call hasBodyDenormalization beforehand',
            ],
            [
                (new RestRequestOptions())->disableBodyValidation(),
                'getBodyValidationOptions',
                'No bodyValidationOptions available, call isBodyValidationNeeded beforehand',
            ],
        ];
    }

    public function testErrorKeepsItsUri()
    {
        $error = (new Error())->setUri('https://example.com/errors/not_found');

        $this->assertSame('https://example.com/errors/not_found', $error->getUri());
    }
}
