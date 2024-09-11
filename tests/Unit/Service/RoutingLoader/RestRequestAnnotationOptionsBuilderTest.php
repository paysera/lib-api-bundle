<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use ReflectionMethod;

class RestRequestAnnotationOptionsBuilderTest extends MockeryTestCase
{
    public function testBuildOptions(): void
    {
        $optionsValidator = Mockery::mock(RestRequestOptionsValidator::class);

        $builder = new RestRequestAnnotationOptionsBuilder($optionsValidator);

        $reflectionMethod = new ReflectionMethod(self::class, 'fixtureMethod');

        $annotationMock1 = Mockery::mock(RestAnnotationInterface::class);
        $annotationMock1->shouldReceive('isSeveralSupported')->andReturn(true);
        $annotationMock1->shouldReceive('apply')->andReturnUsing(function (RestRequestOptions $options) {
            $options->setRequiredPermissions(['modified1']);
        });

        $annotationMock2 = Mockery::mock(RestAnnotationInterface::class);
        $annotationMock2->shouldReceive('isSeveralSupported')->andReturn(true);
        $annotationMock2->shouldReceive('apply')->andReturnUsing(function (RestRequestOptions $options) {
            $options->setResponseNormalizationType('modified2');
        });

        $expectedOptions = (new RestRequestOptions())
            ->setRequiredPermissions(['modified1'])
            ->setResponseNormalizationType('modified2')
        ;

        $optionsValidator
            ->shouldReceive('validateRestRequestOptions')
            ->andReturnUsing(function (RestRequestOptions $options, string $friendlyName) use ($expectedOptions) {
                $this->assertEquals($expectedOptions, $options);
                $this->assertEquals(
                    sprintf('%s::%s', get_class($this), 'fixtureMethod'),
                    $friendlyName
                );
            })
        ;

        $annotations = [
            $annotationMock1,
            $annotationMock2,
        ];

        $options = $builder->buildOptions($annotations, $reflectionMethod);

        $this->assertEquals($expectedOptions, $options);
    }

    public function testBuildOptionsWithSeveralUnsupportedAnnotations(): void
    {
        $optionsValidator = Mockery::mock(RestRequestOptionsValidator::class);

        $builder = new RestRequestAnnotationOptionsBuilder($optionsValidator);

        $this->expectException(ConfigurationException::class);

        $builder->buildOptions([
            new Body(['parameterName' => 'a']),
            new Body(['parameterName' => 'b']),
        ], new ReflectionMethod(self::class, 'fixtureMethod'));
    }

    public function fixtureMethod(): void
    {
        // do nothing
    }
}
