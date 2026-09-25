<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Entity;

use Paysera\Bundle\ApiBundle\Entity\Error;
use Paysera\Bundle\ApiBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UnsetOptionsTest extends TestCase
{
    /**
     * @dataProvider unsetOptionDataProvider
     */
    public function testReadingAnUnsetOptionFails(callable $readOption, string $expectedMessage)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $readOption();
    }

    /**
     * @return array<string, array{0: callable, 1: string}>
     */
    public static function unsetOptionDataProvider(): array
    {
        return [
            'path attribute parameter name' => [
                function () {
                    (new PathAttributeResolverOptions())->getParameterName();
                },
                'parameterName was not set',
            ],
            'path attribute path part name' => [
                function () {
                    (new PathAttributeResolverOptions())->getPathPartName();
                },
                'pathPartName was not set',
            ],
            'path attribute resolver type' => [
                function () {
                    (new PathAttributeResolverOptions())->getPathAttributeResolverType();
                },
                'pathAttributeResolverType was not set',
            ],
            'query parameter name' => [
                function () {
                    (new QueryResolverOptions())->getParameterName();
                },
                'parameterName was not set',
            ],
            'query denormalization type' => [
                function () {
                    (new QueryResolverOptions())->getDenormalizationType();
                },
                'denormalizationType was not set',
            ],
            'query validation options after they were set to null' => [
                function () {
                    (new QueryResolverOptions())->setValidationOptions(null)->getValidationOptions();
                },
                'No validationOptions available, call isValidationNeeded beforehand',
            ],
            'body denormalization type' => [
                function () {
                    (new RestRequestOptions())->getBodyDenormalizationType();
                },
                'No bodyDenormalizationType available, call hasBodyDenormalization beforehand',
            ],
            'body parameter name' => [
                function () {
                    (new RestRequestOptions())->getBodyParameterName();
                },
                'No bodyParameterName available, call hasBodyDenormalization beforehand',
            ],
            'body validation options after validation was disabled' => [
                function () {
                    (new RestRequestOptions())->disableBodyValidation()->getBodyValidationOptions();
                },
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
