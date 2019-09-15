<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Service\RestRequestHelper;
use Paysera\Bundle\RestBundle\Service\RestRequestOptionsRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class RestRequestHelperTest extends MockeryTestCase
{

    public function testSetOptionsForRoute()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $route = new Route('/path');
        $originalOptions = (new RestRequestOptions())
            ->setResponseNormalizationType('my custom options')
        ;
        $helper->setOptionsForRoute($route, $originalOptions);

        $request = new Request();
        $request->attributes->add($route->getDefaults());
        $options = $helper->resolveRestRequestOptions($request, function () {});
        $this->assertEquals($originalOptions, $options);
    }

    public function testIsRestRequest()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $request = new Request();
        $helper->setOptionsForRequest($request, new RestRequestOptions());
        $this->assertSame(true, $helper->isRestRequest($request));

        $this->assertSame(false, $helper->isRestRequest(new Request()));
    }

    public function testGetOptionsFromRequest()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $request = new Request();
        $options = new RestRequestOptions();
        $helper->setOptionsForRequest($request, $options);
        $this->assertSame($options, $helper->getOptionsFromRequest($request));
    }

    public function testResolveRestRequestOptionsWithNoRegisteredOptions()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $request = new Request();

        $this->assertNull($helper->resolveRestRequestOptions($request, function () {}));
    }

    public function testResolveRestRequestOptionsWithRegisteredOptionsAndCustomController()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $request = new Request();
        $request->attributes->set('_controller', 'my custom controller');

        $options = new RestRequestOptions();

        $registry
            ->shouldReceive('getRestRequestOptionsForController')
            ->with('my custom controller')
            ->once()
            ->andReturn($options)
        ;

        $this->assertSame($options, $helper->resolveRestRequestOptions($request, function () {}));
    }

    public function testResolveRestRequestOptionsWithRegisteredOptionsAndClassController()
    {
        $registry = Mockery::mock(RestRequestOptionsRegistry::class);
        $helper = new RestRequestHelper($registry);

        $request = new Request();
        $request->attributes->set('_controller', 'my custom controller');

        $options = new RestRequestOptions();

        $registry
            ->shouldReceive('getRestRequestOptionsForController')
            ->with('my custom controller')
            ->once()
            ->andReturnNull()
        ;
        $registry
            ->shouldReceive('getRestRequestOptionsForController')
            ->with('DateTimeImmutable::createFromFormat')
            ->once()
            ->andReturn($options)
        ;

        $this->assertSame($options, $helper->resolveRestRequestOptions(
            $request,
            ['DateTimeImmutable', 'createFromFormat']
        ));
    }
}
