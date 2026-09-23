<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Attribute;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\PathAttribute;
use Paysera\Bundle\ApiBundle\Attribute\Query;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * What the attributes take from the controller method's signature and what they take from their own arguments.
 */
class AttributeParameterResolutionTest extends TestCase
{
    public function testPathAttributeCannotGuessTheTypeOfAnUntypedParameter()
    {
        $attribute = new PathAttribute(['parameterName' => 'item', 'pathPartName' => 'id']);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Denormalization type could not be guessed for $item in ');

        $attribute->apply(new RestRequestOptions(), $this->wrapUntypedAction());
    }

    public function testQueryCannotGuessTheTypeOfAnUntypedParameter()
    {
        $attribute = new Query(['parameterName' => 'item']);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Denormalization type could not be guessed for $item in ');

        $attribute->apply(new RestRequestOptions(), $this->wrapUntypedAction());
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

        $pathAttributeOptions = $options->getPathAttributeResolverOptionsList()[0];
        $this->assertSame(
            ['custom_resolver', false, true],
            [
                $pathAttributeOptions->getPathAttributeResolverType(),
                $pathAttributeOptions->isResolutionMandatory(),
                $options->isBodyOptional(),
            ]
        );
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
