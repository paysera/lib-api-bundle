<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Listener;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Entity\ValidationOptions;
use Paysera\Bundle\RestBundle\Exception\ForbiddenApiException;
use Paysera\Bundle\RestBundle\Exception\NotFoundApiException;
use Paysera\Bundle\RestBundle\Service\ContentTypeMatcher;
use Paysera\Bundle\RestBundle\Service\RestRequestHelper;
use Paysera\Bundle\RestBundle\Service\Validation\EntityValidator;
use Paysera\Component\Normalization\CoreDenormalizer;
use Paysera\Component\ObjectWrapper\Exception\InvalidItemException;
use Symfony\Component\HttpFoundation\Request;
use Paysera\Component\Normalization\Exception\InvalidDataException;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use stdClass;
use Exception;

/**
 * @internal
 */
class RestRequestListener
{
    private $coreDenormalizer;
    private $authorizationChecker;
    private $tokenStorage;
    private $requestHelper;
    private $entityValidator;
    private $contentTypeMatcher;

    public function __construct(
        CoreDenormalizer $coreDenormalizer,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RestRequestHelper $requestHelper,
        EntityValidator $entityValidator,
        ContentTypeMatcher $contentTypeMatcher
    ) {
        $this->coreDenormalizer = $coreDenormalizer;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->requestHelper = $requestHelper;
        $this->entityValidator = $entityValidator;
        $this->contentTypeMatcher = $contentTypeMatcher;
    }

    /**
     * Ran on kernel.controller event
     *
     * Both events are typecasted as one is deprecated from 4.3, but another not available before this version
     * @param FilterControllerEvent|ControllerEvent $event
     *
     * @throws ApiException
     * @throws InvalidDataException
     * @throws Exception
     */
    public function onKernelController($event)
    {
        $request = $event->getRequest();

        $options = $this->requestHelper->resolveRestRequestOptions($request, $event->getController());
        if ($options === null) {
            return;
        }

        $this->requestHelper->setOptionsForRequest($request, $options);

        $this->checkRequiredPermissions($options);

        $this->addAttributesFromURL($request, $options);
        $this->addAttributesFromQuery($request, $options);
        $this->addAttributesFromBody($request, $options);
    }

    private function checkRequiredPermissions(RestRequestOptions $options)
    {
        if (count($options->getRequiredPermissions()) === 0) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null || $token instanceof AnonymousToken) {
            $exception = new ApiException(
                ApiException::UNAUTHORIZED,
                'This API endpoint requires authentication, none found'
            );
        } else {
            $exception = new ForbiddenApiException(
                'Access to this API endpoint is forbidden for current client'
            );
        }

        foreach ($options->getRequiredPermissions() as $permission) {
            if (!$this->authorizationChecker->isGranted($permission)) {
                throw $exception;
            }
        }
    }

    private function addAttributesFromURL(Request $request, RestRequestOptions $options)
    {
        foreach ($options->getPathAttributeResolverOptionsList() as $pathResolverOptions) {
            $attribute = $request->attributes->get($pathResolverOptions->getPathPartName());
            $value = $this->coreDenormalizer->denormalize(
                $attribute,
                $pathResolverOptions->getDenormalizationType()
            );

            if ($value !== null) {
                $request->attributes->set($pathResolverOptions->getParameterName(), $value);
                continue;
            }

            if ($pathResolverOptions->isResolutionMandatory()) {
                throw new NotFoundApiException('Resource was not found');
            }
        }
    }

    /**
     * Handle request with request query mapper
     *
     * @param Request $request
     * @param RestRequestOptions $options
     *
     * @throws ApiException
     * @throws InvalidItemException
     */
    private function addAttributesFromQuery(Request $request, RestRequestOptions $options)
    {
        foreach ($options->getQueryResolverOptionsList() as $queryResolverOptions) {
            $value = $this->coreDenormalizer->denormalize(
                $this->convertToObject($request->query->all()),
                $queryResolverOptions->getDenormalizationType()
            );

            if ($queryResolverOptions->isValidationNeeded()) {
                $this->entityValidator->validate(
                    $value,
                    $queryResolverOptions->getValidationOptions()
                );
            }

            $request->attributes->set($queryResolverOptions->getParameterName(), $value);
        }
    }

    private function convertToObject(array $query): stdClass
    {
        return (object)json_decode(json_encode($query));
    }

    /**
     * Handles request with request mapper
     *
     * @param Request $request
     * @param RestRequestOptions $options
     *
     * @throws ApiException
     * @throws InvalidItemException
     */
    private function addAttributesFromBody(Request $request, RestRequestOptions $options)
    {
        if (!$options->hasBodyDenormalization()) {
            return;
        }

        $data = $this->decodeBody($request, $options);
        if ($data === null) {
            $this->handleEmptyRequestBody($request, $options);
            return;
        }

        $entity = $this->coreDenormalizer->denormalize(
            $data,
            $options->getBodyDenormalizationType()
        );

        if ($options->isBodyValidationNeeded()) {
            $this->entityValidator->validate(
                $entity,
                $options->getBodyValidationOptions()
            );
        }

        $request->attributes->set($options->getBodyParameterName(), $entity);
    }

    private function decodeBody(Request $request, RestRequestOptions $options)
    {
        $content = $request->getContent();
        if ($content === '') {
            return null;
        }

        $contentType = $request->headers->get('CONTENT_TYPE', '');
        $contentTypeSupported = $this->contentTypeMatcher->isContentTypeSupported(
            $contentType,
            $options->getSupportedRequestContentTypes()
        );
        if (!$contentTypeSupported) {
            throw new ApiException(
                ApiException::INVALID_REQUEST,
                $contentType === ''
                    ? 'Content-Type must be provided'
                    : sprintf('This Content-Type (%s) is not supported', $contentType)
            );
        }

        if (!$options->isJsonEncodedBody()) {
            return $content;
        }

        $data = json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException(
                ApiException::INVALID_REQUEST,
                'Cannot decode request body to JSON'
            );
        }

        return $data;
    }

    private function handleEmptyRequestBody(Request $request, RestRequestOptions $options)
    {
        if (!$options->isBodyOptional()) {
            throw new ApiException(ApiException::INVALID_REQUEST, 'Expected non-empty request body');
        }
    }
}
