<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Listener;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Service\ResponseBuilder;
use Paysera\Bundle\RestBundle\Service\RestRequestHelper;
use Paysera\Component\Normalization\CoreNormalizer;
use Paysera\Component\Normalization\NormalizationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;

/**
 * @internal
 */
class RestResponseListener
{
    private $coreNormalizer;
    private $requestHelper;
    private $responseBuilder;

    public function __construct(
        CoreNormalizer $coreNormalizer,
        RestRequestHelper $requestHelper,
        ResponseBuilder $responseBuilder
    ) {
        $this->coreNormalizer = $coreNormalizer;
        $this->requestHelper = $requestHelper;
        $this->responseBuilder = $responseBuilder;
    }

    /**
     * Both events are typecasted as one is deprecated from 4.3, but another not available before this version
     * @param GetResponseForControllerResultEvent|ViewEvent $event
     */
    public function onKernelView($event)
    {
        $request = $event->getRequest();
        if (!$this->requestHelper->isRestRequest($request)) {
            return;
        }

        $result = $event->getControllerResult();
        $response = $this->createResponse($request, $result);
        $event->setResponse($response);
    }

    private function createResponse(Request $request, $result): Response
    {
        if ($result === null) {
            return $this->responseBuilder->buildEmptyResponse();
        }

        $options = $this->requestHelper->getOptionsFromRequest($request);

        $normalizedResult = $this->normalizeResult($request, $options, $result);

        return $this->responseBuilder->buildResponse($normalizedResult);
    }

    private function normalizeResult(Request $request, RestRequestOptions $options, $result)
    {
        $includedFields = [];
        $fields = $request->query->get('fields');
        if ($fields !== null && is_string($fields) && $fields !== '') {
            $includedFields = explode(',', $fields);
        }

        $responseNormalizationType = $options->getResponseNormalizationType();
        $responseNormalizationGroup = $options->getResponseNormalizationGroup();
        $context = new NormalizationContext($this->coreNormalizer, $includedFields, $responseNormalizationGroup);
        return $this->coreNormalizer->normalize($result, $responseNormalizationType, $context);
    }
}
