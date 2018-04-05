<?php

namespace Paysera\Bundle\RestBundle;

use Paysera\Bundle\RestBundle\Cache\CacheStrategyInterface;
use Paysera\Bundle\RestBundle\Resolver\EntityResolverInterface;
use Paysera\Bundle\RestBundle\Resolver\ServiceAwareAttributeResolver;
use Paysera\Component\Serializer\Factory\EncoderFactoryInterface;
use Paysera\Component\Serializer\Factory\ResponseMapperFactoryInterface;
use Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizer;
use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;
use Paysera\Component\Serializer\Validation\PropertyPathConverterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Paysera\Bundle\RestBundle\Entity\ErrorConfig;
use Paysera\Bundle\RestBundle\Security\SecurityStrategyInterface;
use Paysera\Component\Serializer\Normalizer\NormalizerInterface;

class RestApi
{
    const DEFAULT_VALIDATION_GROUP = 'Default';

    /**
     * @var \Closure[]
     */
    protected $requestMappers;

    /**
     * @var \Closure[]
     */
    protected $requestQueryMappers;

    /**
     * @var \Closure[]
     */
    protected $requestAttributeResolvers;

    /**
     * @var array[]
     */
    protected $requestLoggingParts = array();

    /**
     * @var string[]
     */
    protected $responseMappers;

    /**
     * @var \Paysera\Component\Serializer\Factory\ResponseMapperFactoryInterface[]
     */
    protected $responseMapperFactories;

    /**
     * @var EncoderFactoryInterface[]
     */
    protected $encoderFactories;

    /**
     * @var string[]
     */
    protected $requestFormats = array();

    /**
     * @var string[]
     */
    protected $responseFormats = array();

    /**
     * @var CacheStrategyInterface[]
     */
    protected $cacheStrategies = array();

    /**
     * @var ErrorConfig
     */
    protected $errorConfig;

    /**
     * @var SecurityStrategyInterface
     */
    protected $securityStrategy;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $serviceContainer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    private $globalValidationGroups = [self::DEFAULT_VALIDATION_GROUP];

    /**
     * @var array
     */
    private $validationGroups = [];

    /**
     * @var PropertyPathConverterInterface[]
     */
    protected $controllerPropertyPathConverters = [];

    /**
     * @var PropertyPathConverterInterface
     */
    private $propertyPathConverter;

    /**
     * Constructs object
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $serviceContainer
     * @param LoggerInterface                                           $logger
     */
    public function __construct(
        ContainerInterface $serviceContainer,
        LoggerInterface $logger
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->logger = $logger;
        $this->errorConfig = new ErrorConfig();

        $this->propertyPathConverter = $serviceContainer
            ->get('paysera_rest.service.property_path_converter.camel_case_to_snake_case')
        ;
    }

    /**
     * Adds request mapper for some controller
     *
     * @param string $mapperKey service key, service must implement DenormalizerInterface
     * @param string $controllerKey
     * @param string $argumentName
     * @param array  $validationGroups Validation groups
     * @param PropertyPathConverterInterface|null $propertyPathConverter
     */
    public function addRequestMapper(
        $mapperKey,
        $controllerKey,
        $argumentName,
        array $validationGroups = [self::DEFAULT_VALIDATION_GROUP],
        $propertyPathConverter = null
    ) {
        $controllerKey = $this->normalizeControllerKey($controllerKey);

        $this->requestMappers[$controllerKey] = $this->getDenormalizerClosure($mapperKey, $argumentName);
        $this->addValidationGroups($controllerKey, $validationGroups);
        $this->addControllerPropertyPathConverter($controllerKey, $propertyPathConverter);
    }

    /**
     * Adds request query mapper for some controller
     *
     * @param string $mapperKey     service key, service must implement DenormalizerInterface
     * @param string $controllerKey
     * @param string $argumentName
     * @param array  $validationGroups Validation groups
     * @param PropertyPathConverterInterface|null $propertyPathConverter
     */
    public function addRequestQueryMapper(
        $mapperKey,
        $controllerKey,
        $argumentName,
        array $validationGroups = [self::DEFAULT_VALIDATION_GROUP],
        $propertyPathConverter = null
    ) {
        $controllerKey = $this->normalizeControllerKey($controllerKey);

        $this->requestQueryMappers[$controllerKey] = $this->getDenormalizerClosure($mapperKey, $argumentName);
        $this->addValidationGroups($controllerKey, $validationGroups);
        $this->addControllerPropertyPathConverter($controllerKey, $propertyPathConverter);
    }

    /**
     * Returns denormalizer closure
     * 
     * @param string $mapperKey    Denormalizer mapper key
     * @param string $argumentName Denormalizer name
     * 
     * @return \Closure
     */
    protected function getDenormalizerClosure($mapperKey, $argumentName)
    {
        $serviceContainer = $this->serviceContainer;

        return function() use ($mapperKey, $serviceContainer, $argumentName) {
            $denormalizer = $serviceContainer->get($mapperKey);
            if (!$denormalizer instanceof DenormalizerInterface) {
                throw new \RuntimeException(
                    'Configured service does not implement DenormalizerInterface ' . $mapperKey
                );
            }
            return new NameAwareDenormalizer($denormalizer, $argumentName);
        };
    }

    /**
     * Sets validation group for given controller key, or uses default validation group if given group is empty
     *
     * @param string $controllerKey   Controller key
     * @param array  $validationGroups Validation group array
     */
    protected function addValidationGroups($controllerKey, array $validationGroups)
    {
        $this->validationGroups[$controllerKey] = $validationGroups;
    }

    /**
     * @param string                              $controllerKey
     * @param PropertyPathConverterInterface|null $propertyPathConverter
     */
    public function addControllerPropertyPathConverter($controllerKey, $propertyPathConverter)
    {
        if ($propertyPathConverter !== null) {
            $this->controllerPropertyPathConverters[$controllerKey] = $propertyPathConverter;
        }
    }

    /**
     * @param string $controllerKey
     * @param string|null $part
     */
    public function dontLogRequest($controllerKey, $part = null)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (!$part) {
            $this->requestLoggingParts[$controllerKey] = array(
                'url' => false,
                'header' => false,
                'content' => false
            );
        } else {
            if (!isset($this->requestLoggingParts[$controllerKey])) {
                $this->requestLoggingParts[$controllerKey] = array(
                    'url' => true,
                    'header' => true,
                    'content' => true
                );
            }
            $this->requestLoggingParts[$controllerKey][$part] = false;
        }
    }

    /**
     * Adds an attribute (_controller, _method etc) mapper for a specific controller
     *
     * @param string $serviceKey
     * @param string $controllerKey
     * @param string $attributeName
     * @param string $parameterName
     */
    public function addRequestAttributeResolver($serviceKey, $controllerKey, $attributeName, $parameterName)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        $serviceContainer = $this->serviceContainer;

        if (empty($this->requestAttributeResolvers[$controllerKey])) {
            $this->requestAttributeResolvers[$controllerKey] = array();
        }

        $this->requestAttributeResolvers[$controllerKey][] = function () use (
            $serviceKey,
            $serviceContainer,
            $attributeName,
            $parameterName
        ) {
            $entityResolverService = $serviceContainer->get($serviceKey);

            /** @var EntityResolverInterface $entityResolverService */
            $implementsResolverInterface = $entityResolverService instanceof EntityResolverInterface;
            if (!$implementsResolverInterface) {
                throw new \RuntimeException(
                    'The entity resolver service needs to implement EntityResolverInterface'
                );
            }

            return new ServiceAwareAttributeResolver($entityResolverService, $attributeName, $parameterName);
        };
    }

    /**
     * Adds response mapper for some controller
     *
     * @param string $mapperKey     service key, service must implement NormalizerInterface
     * @param string $controllerKey
     */
    public function addResponseMapper($mapperKey, $controllerKey)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        $this->responseMappers[$controllerKey] = $mapperKey;
    }

    /**
     * Adds response mapper for some controller
     *
     * @param \Paysera\Component\Serializer\Factory\ResponseMapperFactoryInterface $mapperFactory
     * @param string                                                           $controllerKey
     */
    public function addResponseMapperFactory(ResponseMapperFactoryInterface $mapperFactory, $controllerKey)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        $this->responseMapperFactories[$controllerKey] = $mapperFactory;
    }


    public function addCacheStrategy(CacheStrategyInterface $cacheStrategy, $controllerKey)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        $this->cacheStrategies[$controllerKey] = $cacheStrategy;
    }

    /**
     * Adds supported response format
     *
     * @param string $format
     */
    public function addResponseFormat($format)
    {
        $this->responseFormats[] = $format;
    }

    /**
     * Adds supported response format
     *
     * @param string                                                    $format
     * @param \Paysera\Component\Serializer\Factory\EncoderFactoryInterface $encoderFactory
     */
    public function addResponseEncoderFactory($format, EncoderFactoryInterface $encoderFactory)
    {
        $this->responseFormats[] = $format;
        $this->encoderFactories[$format] = $encoderFactory;
    }

    /**
     * Adds supported request format
     *
     * @param string $format
     */
    public function addRequestFormat($format)
    {
        $this->requestFormats[] = $format;
    }

    /**
     * @param array $validationGroups
     */
    public function setValidationGroups(array $validationGroups)
    {
        $this->globalValidationGroups = $validationGroups;
    }

    /**
     * Sets errorConfig
     *
     * @param \Paysera\Bundle\RestBundle\Entity\ErrorConfig $errorConfig
     */
    public function setErrorConfig($errorConfig)
    {
        $this->errorConfig = $errorConfig;
    }

    /**
     * Sets securityStrategy
     *
     * @param \Paysera\Bundle\RestBundle\Security\SecurityStrategyInterface|null $securityStrategy
     */
    public function setSecurityStrategy($securityStrategy)
    {
        $this->securityStrategy = $securityStrategy;
    }

    public function setPropertyPathConverter(PropertyPathConverterInterface $propertyPathConverter)
    {
        $this->propertyPathConverter = $propertyPathConverter;
    }

    /**
     * Returns validation group array
     *
     * @param string $controllerKey
     *
     * @return array|null
     */
    public function getValidationGroups($controllerKey)
    {
        $this->logger->debug('Getting validation group for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);

        if (
            count($this->globalValidationGroups) === 0
            || !array_key_exists($controllerKey, $this->validationGroups)
            || count($this->validationGroups[$controllerKey]) === 0
        ) {
            return null;
        }

        return array_unique(array_merge(
            $this->globalValidationGroups,
            $this->validationGroups[$controllerKey]
        ));
    }

    /**
     * Returns property path converters
     *
     * @param string $controllerKey
     *
     * @return PropertyPathConverterInterface[]
     */
    public function getPropertyPathConverters($controllerKey)
    {
        $this->logger->debug('Getting property path converters for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);

        $propertyPathConverters = [$this->propertyPathConverter];

        if (isset($this->controllerPropertyPathConverters[$controllerKey])) {
            $propertyPathConverters[] = $this->controllerPropertyPathConverters[$controllerKey];
        }

        return $propertyPathConverters;
    }

    /**
     * Should return request mapper for this controller. If none is set, returns null
     *
     * @param string $controllerKey
     *
     * @return \Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizerInterface|null
     */
    public function getRequestMapper($controllerKey)
    {
        $this->logger->debug('Getting request mapper for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (isset($this->requestMappers[$controllerKey])) {
            $closure = $this->requestMappers[$controllerKey];
            return $closure();
        }

        return null;
    }

    /**
     * Should return request query mapper for this controller. If none is set, returns null
     *
     * @param string $controllerKey
     *
     * @return \Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizerInterface|null
     */
    public function getRequestQueryMapper($controllerKey)
    {
        $this->logger->debug('Getting request query mapper for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (isset($this->requestQueryMappers[$controllerKey])) {
            $closure = $this->requestQueryMappers[$controllerKey];
            return $closure();
        }

        return null;
    }

    /**
     * returns if controlers request body should be logged or not
     *
     * @param string $controllerKey
     *
     * @return array
     */
    public function getRequestLoggingParts($controllerKey)
    {
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (isset($this->requestLoggingParts[$controllerKey])) {
            return $this->requestLoggingParts[$controllerKey];
        }

        return array(
            'url' => true,
            'header' => true,
            'content' => false
        );
    }

    /**
     * Should return a request parameter (_controller, _method etc) mapper. If none is set, returns null
     *
     * @param string $controllerKey
     *
     * @return \Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizerInterface|null|array
     */
    public function getRequestAttributeResolvers($controllerKey)
    {
        $this->logger->debug('Getting request attribute mapper for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (isset($this->requestAttributeResolvers[$controllerKey])) {
            $closures = $this->requestAttributeResolvers[$controllerKey];
            return array_map(
                function ($closure) {
                    return $closure();
                },
                $closures
            );
        }

        return array();
    }

    /**
     * Should return response mapper for this controller. If none is set, returns null
     *
     * @param string $controllerKey
     * @param array  $options
     *
     * @return NormalizerInterface|null
     */
    public function getResponseMapper($controllerKey, array $options = array())
    {
        $this->logger->debug('Getting response mapper for ' . $controllerKey);
        $controllerKey = $this->normalizeControllerKey($controllerKey);
        if (isset($this->responseMapperFactories[$controllerKey])) {
            return $this->responseMapperFactories[$controllerKey]->createResponseMapper($options);
        }

        return isset($this->responseMappers[$controllerKey])
            ? $this->serviceContainer->get($this->responseMappers[$controllerKey])
            : null
        ;
    }

    /**
     * @param string $controllerKey
     * @param array  $options
     *
     * @return \Paysera\Bundle\RestBundle\Cache\CacheStrategyInterface|null
     */
    public function getCacheStrategy($controllerKey, array $options = array())
    {
        if (isset($options[CacheStrategyInterface::NO_CACHE]) && $options[CacheStrategyInterface::NO_CACHE]) {
            return null;
        }

        $controllerKey = $this->normalizeControllerKey($controllerKey);
        return isset($this->cacheStrategies[$controllerKey])
            ? $this->cacheStrategies[$controllerKey]
            : null
        ;
    }

    /**
     * Returns formats that are available to format response
     *
     * @return string[]
     */
    public function getAvailableResponseFormats()
    {
        return empty($this->responseFormats) ? array('json') : $this->responseFormats;
    }

    /**
     * Returns formats that are available to parse request content
     *
     * @return string[]
     */
    public function getAvailableRequestFormats()
    {
        return empty($this->requestFormats) ? array('json') : $this->requestFormats;
    }

    /**
     * Returns decoder for specified format. Returns null if default decoder is to be used
     *
     * @param string $format
     *
     * @return \Paysera\Component\Serializer\Encoding\DecoderInterface
     */
    public function getDecoder($format)
    {
        return null;
    }

    /**
     * Returns encoder for specified format. Returns null if default encoder is to be used
     *
     * @param string $format
     * @param array  $options
     *
     * @return \Paysera\Component\Serializer\Encoding\EncoderInterface
     */
    public function getEncoder($format, array $options = array())
    {
        return isset($this->encoderFactories[$format])
            ? $this->encoderFactories[$format]->createEncoder($options)
            : null
        ;
    }

    /**
     * @return SecurityStrategyInterface|null
     */
    public function getSecurityStrategy()
    {
        return $this->securityStrategy;
    }

    /**
     * Returns configuration for error code. Can return null to use default configuration or leave some information
     * empty
     *
     * @param string $errorCode
     *
     * @return array|null    available keys: statusCode, message, uri; value can be null
     */
    public function getErrorConfig($errorCode)
    {
        return $this->errorConfig->getConfig($errorCode);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Returns controller key without namespace prefix. Ie ApiController::getPaymentAction
     *
     * @param string $controllerKey
     *
     * @return string
     */
    protected function normalizeControllerKey($controllerKey)
    {
        $i = strpos($controllerKey, '\\Controller\\');
        if ($i !== false) {
            $controllerKey = substr($controllerKey, $i + 12);
        }
        $controllerKey = str_replace('::', ':', $controllerKey);
        $parts = explode(':', $controllerKey);
        if (count($parts) !== 2) {
            return null;
        }
        $controller = preg_replace('/Controller$/', '', $parts[0]);
        $action = preg_replace('/Action$/', '', $parts[1]);
        return $controller . ':' . $action;
    }
}
