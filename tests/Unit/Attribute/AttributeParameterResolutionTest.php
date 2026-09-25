<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Attribute;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\PathAttribute;
use Paysera\Bundle\ApiBundle\Attribute\Query;
use Paysera\Bundle\ApiBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AttributeParameterResolutionTest extends TestCase
{
    /**
     * @dataProvider untypedParameterAttributeDataProvider
     *
     * @param array<string, string> $attributeOptions
     */
    public function testCannotGuessTheTypeOfAnUntypedParameter(string $attributeClass, array $attributeOptions)
    {
        $attribute = new $attributeClass($attributeOptions);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Denormalization type could not be guessed for $item in ');

        $attribute->apply(new RestRequestOptions(), $this->wrapUntypedAction());
    }

    /**
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function untypedParameterAttributeDataProvider(): array
    {
        return [
            'path attribute' => [PathAttribute::class, ['parameterName' => 'item', 'pathPartName' => 'id']],
            'query' => [Query::class, ['parameterName' => 'item']],
        ];
    }

    public function testExplicitArgumentsWinOverTheSignature()
    {
        $options = new RestRequestOptions();

        (new PathAttribute([
            'parameterName' => 'item',
            'pathPartName' => 'id',
            'resolverType' => 'custom_resolver',
            'resolutionMandatory' => false,
        ]))->apply($options, $this->wrapUntypedAction());
        (new Body([
            'parameterName' => 'item',
            'denormalizationType' => 'custom_type',
            'optional' => true,
        ]))->apply($options, $this->wrapUntypedAction());

        $expectedOptions = (new RestRequestOptions())
            ->addPathAttributeResolverOptions(
                (new PathAttributeResolverOptions())
                    ->setParameterName('item')
                    ->setPathPartName('id')
                    ->setPathAttributeResolverType('custom_resolver')
                    ->setResolutionMandatory(false)
            )
            ->setBodyParameterName('item')
            ->setBodyDenormalizationType('custom_type')
            ->setBodyOptional(true)
        ;
        $this->assertEquals($expectedOptions, $options);
    }

    /**
     * @param mixed $item
     */
    public function untypedAction($item)
    {
    }

    private function wrapUntypedAction(): ReflectionMethodWrapper
    {
        return new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'untypedAction'));
    }
}
