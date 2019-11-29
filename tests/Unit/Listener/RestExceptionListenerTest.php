<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Listener;

use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Entity\Error;
use Paysera\Bundle\ApiBundle\Listener\RestExceptionListener;
use Paysera\Bundle\ApiBundle\Service\ErrorBuilderInterface;
use Paysera\Bundle\ApiBundle\Service\ResponseBuilder;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Component\Normalization\CoreNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RestExceptionListenerTest extends MockeryTestCase
{
    /**
     * @dataProvider provider
     *
     * @param bool $restRequest
     * @param int $statusCode
     * @param string|null $logLevel
     */
    public function testOnKernelException(bool $restRequest, int $statusCode = 400, string $logLevel = null)
    {
        $helper = Mockery::mock(RestRequestHelper::class);
        $errorBuilder = Mockery::mock(ErrorBuilderInterface::class);
        $coreNormalizer = Mockery::mock(CoreNormalizer::class);
        $responseBuilder = Mockery::mock(ResponseBuilder::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $kernel = Mockery::mock(HttpKernelInterface::class);

        $listener = new RestExceptionListener(
            $helper,
            $errorBuilder,
            $coreNormalizer,
            $responseBuilder,
            $logger
        );
        $request = new Request();

        $exception = new Exception('My custom exception');
        if (class_exists('Symfony\Component\HttpKernel\Event\ExceptionEvent')) {
            $event = new ExceptionEvent(
                $kernel,
                $request,
                HttpKernelInterface::MASTER_REQUEST,
                $exception
            );
        } else {
            $event = new GetResponseForExceptionEvent(
                $kernel,
                $request,
                HttpKernelInterface::MASTER_REQUEST,
                $exception
            );
        }

        $helper->shouldReceive('isRestRequest')->with($request)->andReturn($restRequest);

        $error = (new Error())->setCode('custom');
        $response = new Response('custom error', $statusCode);
        $errorBuilder
            ->shouldReceive('createErrorFromException')
            ->with($exception)
            ->andReturn($error)
        ;
        $coreNormalizer
            ->shouldReceive('normalize')
            ->with($error)
            ->andReturn(['error' => 'custom'])
        ;
        $responseBuilder
            ->shouldReceive('buildResponse')
            ->with(['error' => 'custom'], Response::HTTP_BAD_REQUEST)
            ->andReturn($response)
        ;

        $logger->shouldReceive('debug');
        if ($logLevel !== null) {
            $logger->shouldReceive('log')->once()->withSomeOfArgs($logLevel);
        }

        $listener->onKernelException($event);

        if ($restRequest) {
            $this->assertSame($response, $event->getResponse());
        } else {
            $this->assertNull($event->getResponse());
        }
    }

    public function provider()
    {
        return [
            [
                false,
            ],
            [
                true,
                400,
                'warning',
            ],
            [
                true,
                404,
                'notice',
            ],
            [
                true,
                500,
                'error',
            ],
            [
                true,
                504,
                'error',
            ],
        ];
    }
}
