<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use Doctrine\Common\Annotations\AnnotationReader;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOnlyController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;

class RoutingAttributeLoaderTest extends MockeryTestCase
{
    /**
     * @var RestRequestHelper|MockInterface
     */
    private $requestHelper;

    /**
     * @var RestRequestAnnotationOptionsBuilder|MockInterface
     */
    private $annotationOptionsBuilder;

    /**
     * @var RestRequestAttributeOptionsBuilder|MockInterface
     */
    private $attributeOptionsBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestHelper = Mockery::mock(RestRequestHelper::class);
        $this->annotationOptionsBuilder = Mockery::mock(RestRequestAnnotationOptionsBuilder::class);
        $this->attributeOptionsBuilder = Mockery::mock(RestRequestAttributeOptionsBuilder::class);
    }

    public function testRefusesTheBundleDocblockAnnotationsWhereSymfonyReadsNone()
    {
        $this->skipUnlessTheRouteLoaderHasNoAnnotationReader();
        $loader = $this->createLoader();
        $this->requestHelper->shouldNotReceive('setOptionsForRoute');

        try {
            $loader->load(DocblockOptionsOnAttributeRouteController::class);
            $this->fail('The route with the bundle\'s docblock annotations was loaded without them');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(
                DocblockOptionsOnAttributeRouteController::class . '::show() configures its REST endpoint with '
                . 'docblock annotations (' . RequiredPermissions::class . ')',
                $exception->getMessage()
            );
            $this->assertStringContainsString(
                'Use the attributes instead: #[Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions].',
                $exception->getMessage()
            );
        }
    }

    public function testLoadsTheBundleAttributesWhereSymfonyReadsNoDocblocks()
    {
        $this->skipUnlessTheRouteLoaderHasNoAnnotationReader();
        $loader = $this->createLoader();
        $options = new RestRequestOptions();
        $this->attributeOptionsBuilder->shouldReceive('buildOptions')->once()->andReturn($options);
        $this->requestHelper->shouldReceive('setOptionsForRoute')->once()->with(Mockery::any(), $options);

        $routes = $loader->load(AttributeOnlyController::class);

        $this->assertCount(1, $routes);
    }

    public function testAppliesTheBundleDocblockAnnotationsThroughTheReaderBeforeSymfony7()
    {
        if (!class_exists(AttributeRouteControllerLoader::class)
            || !property_exists(AttributeRouteControllerLoader::class, 'reader')
        ) {
            $this->markTestSkipped('Needs Symfony 6.4: the attribute route loader with an annotation reader');
        }
        $loader = $this->createLoader(new AnnotationReader());
        $options = new RestRequestOptions();
        $this->annotationOptionsBuilder
            ->shouldReceive('buildOptions')
            ->once()
            ->with(Mockery::on(function (array $annotations) {
                return count($annotations) === 1 && $annotations[0] instanceof RequiredPermissions;
            }), Mockery::any())
            ->andReturn($options)
        ;
        $this->requestHelper->shouldReceive('setOptionsForRoute')->once()->with(Mockery::any(), $options);

        $routes = $loader->load(DocblockOptionsOnAttributeRouteController::class);

        $this->assertCount(1, $routes);
    }

    private function skipUnlessTheRouteLoaderHasNoAnnotationReader()
    {
        if (!class_exists(AttributeRouteControllerLoader::class)
            || property_exists(AttributeRouteControllerLoader::class, 'reader')
        ) {
            $this->markTestSkipped('Symfony 6.4 and older give the route loader an annotation reader');
        }
    }

    private function createLoader(...$constructorArguments): RoutingAttributeLoader
    {
        $loader = new RoutingAttributeLoader(...$constructorArguments);
        $loader->setRequestHelper($this->requestHelper);
        $loader->setRestRequestAnnotationOptionsBuilder($this->annotationOptionsBuilder);
        $loader->setRestRequestAttributeOptionsBuilder($this->attributeOptionsBuilder);

        return $loader;
    }
}
