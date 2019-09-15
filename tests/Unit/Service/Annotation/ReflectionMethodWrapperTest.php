<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service\Annotation;

use DateTime;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Bundle\RestBundle\Service\Annotation\ReflectionMethodWrapper;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ReflectionMethodWrapperTest extends TestCase
{

    public function testGetParameterByName()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));
        $reflectionParameter = $wrapper->getParameterByName('param2');
        $this->assertSame('param2', $reflectionParameter->getName());
    }

    public function testGetParameterByNameWithNoSuchParameter()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getParameterByName('nonExisting');
    }

    public function testGetNonBuiltInTypeForParameter()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->assertSame('DateTime', $wrapper->getNonBuiltInTypeForParameter('param2'));
    }

    public function testGetNonBuiltInTypeForParameterWithBuiltInType()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getNonBuiltInTypeForParameter('param1');
    }

    public function testGetNonBuiltInTypeForParameterWithNoType()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));

        $this->expectException(ConfigurationException::class);
        $wrapper->getNonBuiltInTypeForParameter('param3');
    }

    public function testGetFriendlyName()
    {
        $wrapper = new ReflectionMethodWrapper(new ReflectionMethod(self::class, 'fixtureMethod'));
        $this->assertSame(
            'Paysera\Bundle\RestBundle\Tests\Unit\Service\Annotation\ReflectionMethodWrapperTest::fixtureMethod',
            $wrapper->getFriendlyName()
        );
    }

    public function fixtureMethod(string $param1, DateTime $param2 = null, $param3 = null): string
    {
        return $param1;
    }
}
