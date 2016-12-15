<?php
namespace Paysera\Bundle\RestBundle\Service;

use Paysera\Bundle\RestBundle\ApiManager;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Paysera\Bundle\RestBundle\Resolver\AttributeResolverInterface;
use Paysera\Component\Serializer\Exception\InvalidDataException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ParameterToEntityMapBuilder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ApiManager
     */
    private $apiManager;

    public function __construct(LoggerInterface $logger, ApiManager $apiManager)
    {
        $this->logger = $logger;
        $this->apiManager = $apiManager;
    }

    /**
     * Builds a list of (parameterName => entity) that can be later appended to the request
     *
     * @param Request $request
     * @return array
     */
    public function buildParameterToEntityMap(Request $request)
    {
        $parameterToEntityMap = array();
        $requestAttributeResolvers = $this->apiManager->getRequestAttributeResolvers($request);

        foreach ($requestAttributeResolvers as $requestAttributeResolver) {
            $attributeName = $requestAttributeResolver->getAttributeName();
            $attributeValue = $this->getAttributeValueFromRequest($request, $attributeName);

            $entity = $this->resolveEntity($requestAttributeResolver, $attributeValue);
            $parameterToEntityMap[$requestAttributeResolver->getParameterName()] = $entity;
        }

        return $parameterToEntityMap;
    }

    /**
     * Retrieves the request attribute value that is needed by the $requestAttributeResolver
     *
     * @param Request $request
     * @param string $attributeName
     * @return mixed
     *
     * @throws InvalidDataException
     */
    private function getAttributeValueFromRequest(
        Request $request,
        $attributeName
    ) {
        $attributes = $request->attributes->all();

        if (empty($attributes[$attributeName])) {
            $this->logger->warning(
                'Could not find the specified attribute name in the request',
                array(
                    'expected attribute' => $attributeName,
                    'received attributes' => $attributes,
                )
            );
            throw new InvalidDataException('Could not find the specified attribute name in the request');
        }
        return $attributes[$attributeName];
    }

    /**
     * Resolves a single entity based on the provided attribute value
     *
     * @param AttributeResolverInterface $requestAttributeResolver
     * @param $attributeValue
     * @return mixed
     *
     * @throws ApiException
     */
    private function resolveEntity(AttributeResolverInterface $requestAttributeResolver, $attributeValue)
    {
        $entity = $requestAttributeResolver->resolveFromAttributeValue($attributeValue);
        $this->logger->debug('Resolved entity from attribute data to ', array($entity));

        if ($entity === null) {
            $this->logger->warning(
                'Could not resolve entity from the attribute value',
                array('attribute value ' => $attributeValue)
            );
            throw new ApiException(
                ApiException::NOT_FOUND,
                'Could not resolve entity from request attributes'
            );
        }

        return $entity;
    }
}
