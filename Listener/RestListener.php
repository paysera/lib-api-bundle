<?php

namespace Paysera\Bundle\RestBundle\Listener;

use Paysera\Bundle\RestBundle\Cache\ResponseAwareCacheStrategy;
use Paysera\Bundle\RestBundle\Service\ParameterToEntityMapBuilder;
use Paysera\Component\Serializer\Entity\NormalizationContext;
use Paysera\Component\Serializer\Factory\ContextAwareNormalizerFactory;
use Paysera\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\HttpFoundation\Request;
use Paysera\Bundle\RestBundle\Entity\RestResponse;
use Paysera\Component\Serializer\Exception\InvalidDataException;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Paysera\Component\Serializer\Exception\EncodingException;
use Paysera\Bundle\RestBundle\ApiManager;
use Paysera\Bundle\RestBundle\Service\RequestLogger;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Psr\Log\LoggerInterface;

class RestListener
{
    private $logger;
    private $requestLogger;
    private $normalizerFactory;
    private $apiManager;
    private $parameterToEntityMapBuilder;

    /**
     * @var LoggerInterface[]
     */
    private $loggersCache;

    public function __construct(
        ApiManager $apiManager,
        ContextAwareNormalizerFactory $normalizerFactory,
        LoggerInterface $logger,
        ParameterToEntityMapBuilder $parameterToEntityMapBuilder,
        RequestLogger $requestLogger
    ) {
        $this->apiManager = $apiManager;
        $this->normalizerFactory = $normalizerFactory;
        $this->logger = $logger;
        $this->parameterToEntityMapBuilder = $parameterToEntityMapBuilder;
        $this->requestLogger = $requestLogger;

        $this->loggersCache = array();
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->apiManager->isRestRequest($request)) {
            $locale = $request->query->get('locale');

            if ($locale !== null) {
                $request->setLocale($locale);
            }
            $request->query->remove('locale');
        }
    }

    /**
     * Ran on kernel.controller event
     *
     * @param FilterControllerEvent $event
     *
     * @throws ApiException
     * @throws InvalidDataException
     * @throws \Exception
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        /** @var $request Request */
        $request = $event->getRequest();
        $logger = $this->getLogger($request);

        $logger->debug('Handling kernel.controller', array($event->getRequest()->attributes->get('_controller')));

        if ($this->apiManager->isRestRequest($request) && $parts = $this->apiManager->getRequestLoggingParts($request)) {
            $this->requestLogger->log($request, $parts);
        }

        $securityStrategy = $this->apiManager->getSecurityStrategy($request);
        if ($securityStrategy !== null && !$securityStrategy->isAllowed($request)) {
            throw new ApiException(ApiException::FORBIDDEN, 'Access to this API is forbidden for current client');
        }

        $this->handleRequestWithRequestQueryMapper($request);

        $this->handleRequestWithRequestMapper($request);

        $parameterToEntityMap = $this->parameterToEntityMapBuilder->buildParameterToEntityMap($request);
        foreach ($parameterToEntityMap as $parameterName => $entity) {
            $request->attributes->add(
                array($parameterName => $entity)
            );
        }
    }

    /**
     * Ran on kernel.view event
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        /** @var $request Request */
        $request = $event->getRequest();
        $logger = $this->getLogger($request);

        $logger->debug('Handling kernel.view', array($event));

        if (!$this->apiManager->isRestRequest($request)) {
            $logger->debug('Not rest request');

            return;
        }

        $result = $event->getControllerResult();
        if ($result instanceof RestResponse) {
            $headers = $result->getHeaders();
            $options = $result->getOptions();
            $result = $result->getResponse();
        } else {
            $headers = array();
            $options = array();
        }

        $response = new Response(null, 200, $headers);

        $cacheStrategy = $this->apiManager->getCacheStrategy($request, $options);

        $maxAge = null;
        $etag = null;
        if ($cacheStrategy !== null) {
            $maxAge = $cacheStrategy->getMaxAge();

            $modifiedAt = $cacheStrategy->getModifiedAt($result);
            if ($modifiedAt !== null) {
                $logger->debug(
                    'Setting modified at',
                    array($modifiedAt, $request->headers->get('If-Modified-Since'))
                );
                $response->setLastModified($modifiedAt);
                $etag = $modifiedAt->getTimestamp();
                if ($response->isNotModified($request)) {
                    $logger->debug('Response not modified - returning 304');
                    $response->setEtag($etag);
                    $event->setResponse($response);
                    return;
                }
            }
        }

        $response->setMaxAge($maxAge === null ? 0 : $maxAge);

        if ($cacheStrategy !== null && $cacheStrategy instanceof ResponseAwareCacheStrategy) {
            $cacheStrategy->setResponse($response);
        } else {
            $response->setPrivate();
        }

        if ($result !== null) {
            $responseMapper = $this->apiManager->getResponseMapper($request, $options);
            if ($responseMapper === null) {
                $logger->debug('No response mapper set');

                return;
            }

            $fields = $request->query->get('fields');
            if ($fields !== null && is_string($fields) && $fields !== '') {
                if (!$responseMapper instanceof ContextAwareNormalizerInterface) {
                    $responseMapper = $this->normalizerFactory->create($responseMapper);
                }
                $context = new NormalizationContext();
                $context->setFields(array($fields));
                $content = $responseMapper->mapFromEntity($result, $context);
            } else {
                $content = $responseMapper->mapFromEntity($result);
            }

            $encoder = $this->apiManager->getEncoder($request, $options);
            $response->headers->set('Content-Type', $encoder->getContentType());
            $responseContent = $encoder->encode($content);

            $logger->debug('Encoded data, setting response');
        } else {
            $response->setStatusCode(204);
            $responseContent = null;

            $logger->debug('Empty response(code: 204)');
        }

        $response->setContent($responseContent);

        $response->setEtag($etag === null ? hash('sha256', $responseContent) : $etag);

        $event->setResponse($response);
    }

    /**
     * Ran on kernel.exception event
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /** @var $request Request */
        $request = $event->getRequest();
        $logger = $this->getLogger($request);

        $logger->debug('Handling kernel.exception', array($event));
        $logger->debug($event->getException());

        $response = $this->apiManager->getResponseForException($request, $event->getException());
        if ($response !== null) {
            $event->setResponse($response);
            $logger->debug('Setting error response', array($response->getContent()));

            if ($response->getStatusCode() === 500) {
                $logger->error((string)$event->getException(), array($event->getException()));
            } elseif ($response->getStatusCode() === 404) {
                $logger->notice((string)$event->getException(), array($event->getException()));
            } else {
                $logger->warning((string)$event->getException(), array($event->getException()));
            }
        }
    }

    /**
     * Validates entity
     *
     * @param Request $request Request to get validation group
     * @param object  $entity  Entity to be validated
     *
     * @throws ApiException
     */
    protected function validateEntity($request, $entity)
    {
        try {
            $validationGroups = $this->apiManager->getValidationGroups($request);
            if (is_array($validationGroups) && count($validationGroups) > 0) {
                $this->getLogger($request)->debug('Validating entity', array($entity));

                $propertiesValidator = $this->apiManager->createPropertiesValidator($request);
                if ($propertiesValidator !== null) {
                    $propertiesValidator->validate($entity, $validationGroups);
                }
            }
        } catch (InvalidDataException $exception) {
            $this->getLogger($request)->notice(
                'Invalid data exception caught: ' . $exception,
                count($exception->getProperties()) > 0 ? $exception->getProperties() : array()
            );
            $this->handleException($exception);
        }
    }

    /**
     * Handles request with request mapper
     *
     * @param Request $request
     *
     * @throws ApiException
     */
    protected function handleRequestWithRequestMapper($request)
    {
        $requestMapper = $this->apiManager->getRequestMapper($request);
        if ($requestMapper !== null) {
            $content = $request->getContent();
            if ($content == '') {
                $data = null;
            } else {
                try {
                    $data = $this->apiManager->getDecoder($request)->decode($content);
                } catch (EncodingException $exception) {
                    throw new ApiException(
                        ApiException::INVALID_REQUEST,
                        'Content of request is not valid in this format'
                    );
                }
            }
            try {
                $entity = $requestMapper->mapToEntity($data);
            } catch (InvalidDataException $exception) {
                $this->handleException($exception);
            }
            $this->getLogger($request)->debug('Mapped data to entity', array($entity));

            $this->validateEntity($request, $entity);

            $request->attributes->add(array($requestMapper->getName() => $entity));
        }
    }

    /**
     * Handle request with request query mapper
     *
     * @param Request $request
     *
     * @throws ApiException
     */
    protected function handleRequestWithRequestQueryMapper($request)
    {
        $requestQueryMapper = $this->apiManager->getRequestQueryMapper($request);
        if ($requestQueryMapper !== null) {
            try {
                $entity = $requestQueryMapper->mapToEntity($request->query->all());
            } catch (InvalidDataException $exception) {
                $this->handleException($exception);
            }
            $this->getLogger($request)->debug('Mapped query data to entity', array($entity));

            $this->validateEntity($request, $entity);

            $request->attributes->add(array($requestQueryMapper->getName() => $entity));
        }
    }

    /**
     * Throws given InvalidDataException as ApiException
     * 
     * @param InvalidDataException $exception
     * 
     * @throws ApiException
     */
    protected function handleException(InvalidDataException $exception)
    {
        throw new ApiException(
            $exception->getCustomCode() ?: ApiException::INVALID_PARAMETERS,
            $exception->getMessage(),
            null,
            $exception,
            $exception->getProperties()
        );
    }

    /**
     * @param Request $request
     * @return LoggerInterface
     */
    private function getLogger(Request $request)
    {
        $apiKey = $this->apiManager->getApiKeyForRequest($request);

        if (isset($this->loggersCache[$apiKey])) {
            return $this->loggersCache[$apiKey];
        }

        $logger = $this->apiManager->getLogger($request);
        if ($logger === null) {
            $logger = $this->logger;
        }
        $this->loggersCache[$apiKey] = $logger;

        return $logger;
    }
}
