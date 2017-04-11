<?php

namespace Tests;

use Paysera\Bundle\RestBundle\RestApi;

class RestApiTest extends \PHPUnit_Framework_TestCase
{
    private $serviceContainer;
    private $logger;
    private $messageStorage = [];
    private $controllerKey = 'Key:Key';
    
    public function setUp()
    {
        $this->serviceContainer = \Mockery::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        
        $this->logger = \Mockery::mock('Psr\Log\LoggerInterface');
        $this->logger->shouldReceive('debug')->andReturnUsing($this->storeMessage());
    }
    
    public function controllerKeyProvider()
    {
        return [
            ['Package\Controller\SomeController::someAction', 'Some:some'],
            ['Package\Controller\Some::someAction', 'Some:some'],
            ['Package\Controller\Some::some', 'Some:some'],
            ['Controller\SomeController::someAction', 'Controller\Some:some'],
            ['Controller\SomeCoantroller::someAction', 'Controller\SomeCoantroller:some'],
            ['Some:some', 'Some:some'],
            ['SomeController', ''],
            ['SomeController::distortedAcction', 'Some:distortedAcction'],
            ['DistortedControllller::distortedAcction', 'DistortedControllller:distortedAcction'],
        ];
    }

    /**
     * @dataProvider controllerKeyProvider
     */
    public function testNormalizeControllerKeyUsingGetValidationGroups($controllerKey)
    {
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->getValidationGroups($controllerKey);
        $this->assertStringEndsWith($controllerKey, $this->messageStorage[0]);
    }

    public function testGetValidationGroupAfterSettingRequestMapperWithGroup()
    {
        $validationGroup = [1, 2];
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->addRequestMapper('key', $this->controllerKey, 'argument', $validationGroup);
        $this->assertEquals(
            array_merge(['Default'], $validationGroup),
            $restApi->getValidationGroups($this->controllerKey)
        );
    }

    public function testGetValidationGroupAfterSettingRequestMapperWithoutGroup()
    {
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->addRequestMapper('key', $this->controllerKey, 'argument');
        $this->assertEquals(['Default'], $restApi->getValidationGroups($this->controllerKey));
    }

    public function testSetRequestMapperAndSetRequestQueryMapperProducesValidDenormalizer()
    {
        $mapperKey = 'key';
        $argument = 'argument';
        $mockedDenormalizer = \Mockery::mock('Paysera\Component\Serializer\Normalizer\DenormalizerInterface');
        $this->serviceContainer->shouldReceive('get')->with($mapperKey)->andReturn($mockedDenormalizer);

        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->addRequestMapper($mapperKey, $this->controllerKey, $argument);
        $restApi->addRequestQueryMapper($mapperKey, $this->controllerKey, $argument);

        $requestMapperDenormalizer = $restApi->getRequestMapper($this->controllerKey);
        $requestQueryMapperDenormalizer = $restApi->getRequestQueryMapper($this->controllerKey);

        $expectedInterface = 'Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizerInterface';
        $this->assertInstanceOf($expectedInterface, $requestMapperDenormalizer);
        $this->assertInstanceOf($expectedInterface, $requestQueryMapperDenormalizer);
        $this->assertSame($argument, $requestQueryMapperDenormalizer->getName());
        $this->assertSame($argument, $requestMapperDenormalizer->getName());
    }

    public function testSetRequestMapperThrowsExceptionWhenDenormalizerIsIncorrect()
    {
        $this->setExpectedException('\RuntimeException');
        $mapperKey = 'key';
        $mockedDenormalizer = \Mockery::mock('RandomalizerInterface');
        $this->serviceContainer->shouldReceive('get')->with($mapperKey)->andReturn($mockedDenormalizer);
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->addRequestMapper($mapperKey, $this->controllerKey, 'argument');
        $restApi->getRequestMapper($this->controllerKey);
    }

    public function testSetRequestQueryMapperThrowsExceptionWhenDenormalizerIsIncorrect()
    {
        $this->setExpectedException('\RuntimeException');
        $mapperKey = 'key';
        $mockedDenormalizer = \Mockery::mock('RandomalizerInterface');
        $this->serviceContainer->shouldReceive('get')->with($mapperKey)->andReturn($mockedDenormalizer);
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $restApi->addRequestQueryMapper($mapperKey, $this->controllerKey, 'argument');
        $restApi->getRequestQueryMapper($this->controllerKey);
    }

    public function testGetRequestMapperAndGetRequestQueryMapperReturnsNullOnMissingDenormalizer()
    {
        $restApi = new RestApi($this->serviceContainer, $this->logger);
        $this->assertNull($restApi->getRequestMapper($this->controllerKey));
        $this->assertNull($restApi->getRequestQueryMapper($this->controllerKey));
    }

    private function storeMessage()
    {
        return function ($value) {
            $this->messageStorage[] = $value;
        };
    }
}
