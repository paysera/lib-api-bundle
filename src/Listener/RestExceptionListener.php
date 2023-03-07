<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Listener;

use Paysera\Bundle\ApiBundle\Service\ResponseBuilder;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Component\Normalization\CoreNormalizer;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Psr\Log\LoggerInterface;
use Exception;
use Paysera\Bundle\ApiBundle\Service\ErrorBuilderInterface;
use Throwable;

/**
 * @internal
 */
class RestExceptionListener
{
    private $requestHelper;
    private $errorBuilder;
    private $coreNormalizer;
    private $responseBuilder;
    private $logger;

    public function __construct(
        RestRequestHelper $requestHelper,
        ErrorBuilderInterface $errorBuilder,
        CoreNormalizer $coreNormalizer,
        ResponseBuilder $responseBuilder,
        LoggerInterface $logger = null
    ) {
        $this->requestHelper = $requestHelper;
        $this->errorBuilder = $errorBuilder;
        $this->coreNormalizer = $coreNormalizer;
        $this->responseBuilder = $responseBuilder;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Ran on kernel.exception event
     *
     * Both events are typecasted as one is deprecated from 4.3, but another not available before this version
     * @param GetResponseForExceptionEvent|ExceptionEvent $event
     * @throws Exception
     */
    public function onKernelException($event)
    {
        $request = $event->getRequest();

        if (!$this->requestHelper->isRestRequest($request)) {
            return;
        }

        $exception = $event->getThrowable();
        $error = $this->errorBuilder->createErrorFromException($exception);
        $normalizedError = $this->coreNormalizer->normalize($error);

        $response = $this->responseBuilder->buildResponse(
            $normalizedError,
            $error->getStatusCode() ?? Response::HTTP_BAD_REQUEST
        );

        $this->logException($response, $exception);

        $event->setResponse($response);
    }

    private function logException(Response $response, Throwable $exception)
    {
        if ($response->getStatusCode() >= 500) {
            $level = LogLevel::ERROR;
        } elseif ($response->getStatusCode() === 404) {
            $level = LogLevel::NOTICE;
        } else {
            $level = LogLevel::WARNING;
        }

        $this->logger->debug('Setting error response', [$response->getContent()]);
        $this->logger->log($level, $exception->getMessage(), ['exception' => $exception]);
    }
}
