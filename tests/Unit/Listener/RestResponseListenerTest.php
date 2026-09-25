<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Listener;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Listener\RestResponseListener;
use Paysera\Bundle\ApiBundle\Service\ResponseBuilder;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Tests\Unit\Helper\HttpKernelHelper;
use Paysera\Component\Normalization\CoreNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RestResponseListenerTest extends MockeryTestCase
{
    public function testLeavesTheResponseOfANonRestRequestAlone()
    {
        $requestHelper = Mockery::mock(RestRequestHelper::class);
        $requestHelper->shouldReceive('isRestRequest')->andReturn(false);
        $event = $this->createViewEvent(['a' => 'result']);

        $this->createListener($requestHelper, Mockery::mock(ResponseBuilder::class))->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testAnswersAControllerThatReturnsNothingWithAnEmptyResponse()
    {
        $requestHelper = Mockery::mock(RestRequestHelper::class);
        $requestHelper->shouldReceive('isRestRequest')->andReturn(true);
        $emptyResponse = new Response('', Response::HTTP_NO_CONTENT);
        $responseBuilder = Mockery::mock(ResponseBuilder::class);
        $responseBuilder->shouldReceive('buildEmptyResponse')->once()->andReturn($emptyResponse);
        $event = $this->createViewEvent(null);

        $this->createListener($requestHelper, $responseBuilder)->onKernelView($event);

        $this->assertSame($emptyResponse, $event->getResponse());
    }

    private function createListener($requestHelper, $responseBuilder): RestResponseListener
    {
        return new RestResponseListener(Mockery::mock(CoreNormalizer::class), $requestHelper, $responseBuilder);
    }

    private function createViewEvent($controllerResult)
    {
        $kernel = Mockery::mock(HttpKernelInterface::class);
        $requestType = HttpKernelHelper::getMainRequestConstValue();
        if (class_exists(ViewEvent::class)) {
            return new ViewEvent($kernel, new Request(), $requestType, $controllerResult);
        }

        return new GetResponseForControllerResultEvent($kernel, new Request(), $requestType, $controllerResult);
    }
}
