<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use DateTime;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\ReflectionMethodWrapper;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReflectionMethodWrapperTest extends TestCase
{
    public function testGetParameterByName(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));
        $reflectionParameter = $wrapper->getParameterByName('param2');
        $this->assertSame('param2', $reflectionParameter->getName());
    }

    public function testGetParameterByNameWithNoSuchParameter(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getParameterByName('nonExisting');
    }

    public function testGetNonBuiltInTypeForParameter(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->assertSame('DateTime', $wrapper->getNonBuiltInTypeForParameter('param2'));
    }

    public function testGetNonBuiltInTypeForParameterWithBuiltInType(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getNonBuiltInTypeForParameter('param1');
    }

    public function testGetNonBuiltInTypeForParameterWithNoType(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getNonBuiltInTypeForParameter('param3');
    }

    public function testGetFriendlyName(): void
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));
        $this->assertSame(
            sprintf('%s::%s', get_class($this), 'fixtureMethod'),
            $wrapper->getFriendlyName()
        );
    }

    public function fixtureMethod(string $param1, DateTime $param2 = null, $param3 = null): string
    {
        return $param1;
    }
}
