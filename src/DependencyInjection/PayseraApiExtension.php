<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\DependencyInjection;

use Doctrine\Persistence\ObjectRepository;
use Paysera\Bundle\ApiBundle\Listener\LocaleListener;
use Paysera\Bundle\ApiBundle\Listener\RestExceptionListener;
use Paysera\Bundle\ApiBundle\Listener\RestRequestListener;
use Paysera\Bundle\ApiBundle\Listener\RestResponseListener;
use Paysera\Bundle\ApiBundle\Normalizer\ErrorNormalizer;
use Paysera\Bundle\ApiBundle\Normalizer\Pagination\PagedQueryNormalizer;
use Paysera\Bundle\ApiBundle\Normalizer\Pagination\PagerDenormalizer;
use Paysera\Bundle\ApiBundle\Normalizer\Pagination\ResultNormalizer;
use Paysera\Bundle\ApiBundle\Normalizer\ViolationNormalizer;
use Paysera\Bundle\ApiBundle\Service\ContentTypeMatcher;
use Paysera\Bundle\ApiBundle\Service\ErrorBuilder;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\DoctrinePathAttributeResolver;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolutionManager;
use Paysera\Bundle\ApiBundle\Service\PathAttributeResolver\PathAttributeResolverRegistry;
use Paysera\Bundle\ApiBundle\Service\ResponseBuilder;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsRegistry;
use Paysera\Bundle\ApiBundle\Service\RestRequestOptionsValidator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationReader;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAnnotationOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRequestAttributeOptionsBuilder;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RestRouteConfigurator;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAnnotationLoader;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\RoutingAttributeLoader;
use Paysera\Bundle\ApiBundle\Service\Validation\CamelCaseToSnakeCaseConverter;
use Paysera\Bundle\ApiBundle\Service\Validation\EntityValidator;
use Paysera\Pagination\Service\CursorBuilder;
use Paysera\Pagination\Service\Doctrine\QueryAnalyser;
use Paysera\Pagination\Service\Doctrine\ResultProvider;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PayseraApiExtension extends Extension
{
    /**
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (count($config['locales']) > 0) {
            $container->setDefinition('paysera_api.listener.locale', $this->createLocaleListenerDefinition());
        }
        $container->addDefinitions($this->createListenerDefinitions());
        $container->addDefinitions($this->createNormalizerDefinitions());
        $container->addDefinitions($this->createPaginationDefinitions());
        $container->addDefinitions($this->createServiceDefinitions());
        $container->setAlias(
            'paysera_api.validation.property_path_converter',
            $config['validation']['property_path_converter']
                ?? new Alias('paysera_api.camel_case_to_snake_case_converter', false)
        );
        $container->addDefinitions($this->createRoutingLoaderDefinitions());

        $container->setParameter('paysera_api.locales', $config['locales']);

        if (count($config['path_attribute_resolvers']) > 0 && !class_exists('Doctrine\ORM\EntityManager')) {
            throw new RuntimeException(
                'Please install doctrine/orm before configuring paysera_api.path_attribute_resolvers'
            );
        }

        foreach ($config['path_attribute_resolvers'] as $className => $resolverConfig) {
            $container->setDefinition(
                'paysera_api.auto_registered.path_attribute_resolver.' . $className,
                $this->buildPathAttributeResolverDefinition($className, $resolverConfig['field'])
            );
        }

        $this->configurePagination($container, $config['pagination']);
    }

    private function createLocaleListenerDefinition(): Definition
    {
        return (new Definition(LocaleListener::class, [
            new Reference('paysera_api.rest_request_helper'),
            '%paysera_api.locales%',
        ]))->addTag('kernel.event_listener', ['event' => 'kernel.request', 'priority' => 20]);
    }

    /**
     * @return array<string, Definition>
     */
    private function createListenerDefinitions(): array
    {
        $restRequestHelper = new Reference('paysera_api.rest_request_helper');
        $coreNormalizer = new Reference('paysera_normalization.core_normalizer');
        $responseBuilder = new Reference('paysera_api.response_builder');

        return [
            'paysera_api.listener.rest_exception' => (new Definition(RestExceptionListener::class, [
                $restRequestHelper,
                new Reference('paysera_api.error_builder'),
                $coreNormalizer,
                $responseBuilder,
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ]))->addTag('kernel.event_listener', ['event' => 'kernel.exception', 'priority' => 1]),
            'paysera_api.listener.rest_request' => (new Definition(RestRequestListener::class, [
                new Reference('paysera_normalization.core_denormalizer'),
                new Reference('security.authorization_checker'),
                new Reference('security.token_storage'),
                $restRequestHelper,
                new Reference('paysera_api.entity_validator'),
                new Reference('paysera_api.content_type_matcher'),
                new Reference('paysera_api.path_attribute_resolution_manager'),
            ]))
                ->addTag('kernel.event_listener', ['event' => 'kernel.controller'])
                ->addTag('kernel.event_listener', ['event' => 'kernel.request', 'priority' => 31]),
            'paysera_api.listener.rest_response' => (new Definition(RestResponseListener::class, [
                $coreNormalizer,
                $restRequestHelper,
                $responseBuilder,
            ]))->addTag('kernel.event_listener', ['event' => 'kernel.view']),
        ];
    }

    /**
     * @return array<string, Definition>
     */
    private function createNormalizerDefinitions(): array
    {
        $tag = 'paysera_normalization.autoconfigured_normalizer';

        return [
            'paysera_api.normalizer.violation' => (new Definition(ViolationNormalizer::class))->addTag($tag),
            'paysera_api.normalizer.error' => (new Definition(ErrorNormalizer::class))->addTag($tag),
            'paysera_api.normalizer.result' => (new Definition(ResultNormalizer::class))->addTag($tag),
            'paysera_api.normalizer.paged_query' => (new Definition(PagedQueryNormalizer::class, [
                new Reference('paysera_api.pagination.result_provider'),
                '%paysera_api.pagination.default_total_count_strategy%',
                '%paysera_api.pagination.maximum_offset%',
            ]))->addTag($tag),
            'paysera_api.normalizer.pager_denormalizer' => (new Definition(PagerDenormalizer::class, [
                '%paysera_api.pagination.default_limit%',
                '%paysera_api.pagination.maximum_limit%',
            ]))->addTag($tag),
        ];
    }

    /**
     * @return array<string, Definition>
     */
    private function createPaginationDefinitions(): array
    {
        $propertyAccessor = (new Definition(PropertyAccessor::class))
            ->setFactory([PropertyAccess::class, 'createPropertyAccessor'])
        ;

        return [
            'paysera_api.pagination.result_provider' => new Definition(ResultProvider::class, [
                new Definition(QueryAnalyser::class),
                new Definition(CursorBuilder::class, [$propertyAccessor]),
            ]),
        ];
    }

    /**
     * @return array<string, Definition>
     */
    private function createServiceDefinitions(): array
    {
        $optionsValidator = new Reference('paysera_api.rest_request_options_validator');
        $resolverRegistry = new Reference('paysera_api.path_attribute_resolver_registry');

        return [
            'paysera_api.rest_request_options_registry' => new Definition(RestRequestOptionsRegistry::class),
            'paysera_api.rest_request_helper' => new Definition(RestRequestHelper::class, [
                new Reference('paysera_api.rest_request_options_registry'),
            ]),
            'paysera_api.annotations.options_builder' => new Definition(
                RestRequestAnnotationOptionsBuilder::class,
                [$optionsValidator]
            ),
            'paysera_api.attributes.options_builder' => new Definition(
                RestRequestAttributeOptionsBuilder::class,
                [$optionsValidator]
            ),
            'paysera_api.docblock_annotation_reader' => new Definition(DocblockAnnotationReader::class),
            'paysera_api.rest_route_configurator' => new Definition(RestRouteConfigurator::class, [
                new Reference('paysera_api.rest_request_helper'),
                new Reference('paysera_api.annotations.options_builder'),
                new Reference('paysera_api.attributes.options_builder'),
                new Reference('paysera_api.docblock_annotation_reader'),
            ]),
            'paysera_api.response_builder' => new Definition(ResponseBuilder::class),
            'paysera_api.error_builder' => $this->createErrorBuilderDefinition(),
            'paysera_api.content_type_matcher' => new Definition(ContentTypeMatcher::class),
            'paysera_api.camel_case_to_snake_case_converter' => new Definition(CamelCaseToSnakeCaseConverter::class),
            'paysera_api.entity_validator' => new Definition(EntityValidator::class, [
                new Reference('validator', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('paysera_api.validation.property_path_converter'),
            ]),
            'paysera_api.rest_request_options_validator' => new Definition(RestRequestOptionsValidator::class, [
                new Reference('paysera_normalization.normalizer_registry'),
                $resolverRegistry,
            ]),
            'paysera_api.path_attribute_resolver_registry' => new Definition(PathAttributeResolverRegistry::class),
            'paysera_api.path_attribute_resolution_manager' => new Definition(
                PathAttributeResolutionManager::class,
                [$resolverRegistry]
            ),
        ];
    }

    private function createErrorBuilderDefinition(): Definition
    {
        $definition = new Definition(ErrorBuilder::class);
        $errors = [
            ['invalid_request', 400, 'Request content is invalid'],
            ['invalid_parameters', 400, 'Some required parameter is missing or it\'s format is invalid'],
            ['invalid_state', 409, 'Requested action cannot be made to the current state of resource'],
            ['unauthorized', 401, 'You have not provided any credentials or they are invalid'],
            ['forbidden', 403, 'You have no rights to access requested resource or make requested action'],
            ['not_found', 404, 'Resource was not found'],
            ['internal_server_error', 500, 'Unexpected internal system error'],
            ['not_acceptable', 406, 'Unknown request or response format'],
        ];
        foreach ($errors as $error) {
            $definition->addMethodCall('configureError', $error);
        }

        return $definition;
    }

    /**
     * @return array<string, Definition>
     */
    private function createRoutingLoaderDefinitions(): array
    {
        $restRouteConfigurator = new Reference('paysera_api.rest_route_configurator');

        if (!class_exists(AttributeRouteControllerLoader::class)) {
            return [
                'paysera_api.annotations.loader' => (new ChildDefinition('routing.loader.annotation'))
                    ->setClass(RoutingAnnotationLoader::class)
                    ->setDecoratedService('routing.loader.annotation')
                    ->addMethodCall('setRestRouteConfigurator', [$restRouteConfigurator]),
            ];
        }

        $attributeLoader = (new ChildDefinition('routing.loader.attribute'))
            ->setClass(RoutingAttributeLoader::class)
            ->setDecoratedService('routing.loader.attribute')
            ->addMethodCall('setRestRouteConfigurator', [$restRouteConfigurator])
        ;

        return ['paysera_api.loader.attribute' => $attributeLoader];
    }

    private function buildPathAttributeResolverDefinition(string $className, string $field): Definition
    {
        $repositoryDefinition = (new Definition(ObjectRepository::class, [$className]))
            ->setFactory([new Reference('doctrine.orm.entity_manager'), 'getRepository'])
        ;

        return (new Definition(DoctrinePathAttributeResolver::class, [
            $repositoryDefinition,
            $field,
        ]))->addTag('paysera_api.path_attribute_resolver', ['type' => $className]);
    }

    private function configurePagination(ContainerBuilder $container, array $paginationConfig)
    {
        $container->setParameter(
            'paysera_api.pagination.default_total_count_strategy',
            $paginationConfig['total_count_strategy']
        );
        $container->setParameter(
            'paysera_api.pagination.maximum_offset',
            $paginationConfig['maximum_offset']
        );
        $container->setParameter(
            'paysera_api.pagination.default_limit',
            $paginationConfig['default_limit']
        );
        $container->setParameter(
            'paysera_api.pagination.maximum_limit',
            $paginationConfig['maximum_limit']
        );
    }
}
