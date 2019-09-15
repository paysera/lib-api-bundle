<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional;

use Paysera\Bundle\RestBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\RestBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Entity\ValidationOptions;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class FunctionalRestBundleTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setUpContainer('basic');
    }

    /**
     * @dataProvider restRequestsConfigurationProvider
     * @param Response $expectedResponse
     * @param Request $request
     */
    public function testRestRequestConfiguration(Response $expectedResponse, Request $request)
    {
        $response = $this->handleRequest($request);
        $this->assertEquals(
            $expectedResponse->getContent(),
            $response->getContent(),
            'expected correct content'
        );
        $this->assertEquals(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode(),
            'expected correct status code'
        );
    }

    public function restRequestsConfigurationProvider()
    {
        return [
            'config does NOT work by full controller name (Bundle:Controller:action)' => [
                new Response('default'),
                $this->createRequest('GET', '/config/by-full-controller?query_parameter=works'),
            ],
            'config works by full class name when using bundle notation routing (Bundle:Controller:action)' => [
                new Response('works'),
                $this->createRequest('GET', '/config/by-full-controller-and-class-name?query_parameter=works'),
            ],
            'config works by class name' => [
                new Response('works'),
                $this->createRequest('GET', '/config/by-class-name?query_parameter=works'),
            ],
            'config works by service name' => [
                new Response('works'),
                $this->createRequest('GET', '/config/by-service-name?query_parameter=works'),
            ],
            'config works by service name and class name' => [
                new Response('works'),
                $this->createRequest(
                    'GET',
                    '/config/routing-by-service-name-config-by-class-name?query_parameter=works'
                ),
            ],
            'config does NOT work by class name and service name' => [
                new Response('default'),
                $this->createRequest(
                    'GET',
                    '/config/routing-by-class-name-config-by-service-name?query_parameter=works'
                ),
            ],
        ];
    }

    /**
     * @dataProvider restRequestsWithOptionsProvider
     * @param Response $expectedResponse
     * @param Request $request
     * @param RestRequestOptions $options
     */
    public function testRestRequestWithOptions(Response $expectedResponse, Request $request, RestRequestOptions $options)
    {
        $this->kernel->getContainer()->get('rest_registry')->registerRestRequestOptions(
            $options,
            $this->kernel->getContainer()->get('router')->match($request->getPathInfo())['_controller']
        );

        $response = $this->handleRequest($request);

        if ($expectedResponse->getStatusCode() !== 500 && $response->getStatusCode() === 500) {
            $logs = $this->kernel->getContainer()->get('logger')->cleanLogs();
            foreach ($logs as $logEntry) {
                if ($logEntry[0] === 'error') {
                    throw new RuntimeException($logEntry[1]);
                }
            }
        }

        $this->assertEquals(
            $expectedResponse->getContent(),
            $response->getContent(),
            'expected correct content'
        );
        $this->assertEquals(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode(),
            'expected correct status code'
        );
    }

    public function restRequestsWithOptionsProvider()
    {
        return [
            'body denormalization works without json' => [
                new Response('prefixed_body'),
                $this->createRequest(
                    'POST',
                    '/',
                    'body'
                ),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('prefixed')
                    ->setBodyParameterName('parameter')
                    ->setSupportedContentTypes(['*']),
            ],
            'validates empty content type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"Expected non-empty request body"}',
                    400
                ),
                $this->createRequest('POST', '/'),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('prefixed')
                    ->setBodyParameterName('parameter')
                    ->setSupportedContentTypes(['image/jpeg']),
            ],
            'validates different content type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (application/json) is not supported"}',
                    400
                ),
                $this->createJsonRequest('POST', '/', ['key' => 'value']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('prefixed')
                    ->setBodyParameterName('parameter')
                    ->setSupportedContentTypes(['image/jpeg']),
            ],
            'accepts wildcards in content type' => [
                new Response('prefixed_text'),
                $this->createRequest('POST', '/', 'text', ['Content-Type' => 'text/plain']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('prefixed')
                    ->setBodyParameterName('parameter')
                    ->setSupportedContentTypes(['image/jpeg', 'text/*']),
            ],
            'body denormalization works with json, accepts JSON by default' => [
                new Response('value'),
                $this->createJsonRequest('POST', '/', ['key' => 'value']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('extract:key')
                    ->setBodyParameterName('parameter'),
            ],
            'accepts JSON with no Content-Type by default' => [
                new Response('value'),
                $this->createRequest('POST', '/', json_encode(['key' => 'value'])),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('extract:key')
                    ->setBodyParameterName('parameter'),
            ],

            'body denormalization works with optional body and leaves default argument values' => [
                new Response('default'),
                $this->createRequest('POST', '/'),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('extract:key')
                    ->setBodyParameterName('parameter')
                    ->setBodyOptional(true),
            ],
            'body denormalization validates required body' => [
                new Response(
                    '{"error":"invalid_request","error_description":"Expected non-empty request body"}',
                    400
                ),
                $this->createRequest('POST', '/'),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('extract:key')
                    ->setBodyParameterName('parameter'),
            ],
            'body denormalization works with object denormalizer' => [
                new Response('MyObject:value1'),
                $this->createJsonRequest('POST', '/', ['field1' => 'value1']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter'),
            ],
            'body denormalization works with object denormalizer and 2 level deep objects' => [
                new Response('MyObject:value1.internal value 1'),
                $this->createJsonRequest('POST', '/', [
                    'field1' => 'value1.',
                    'internal' => ['field1' => 'internal value 1'],
                ]),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter'),
            ],
            'body validation is used by default' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"This value should not be blank.","field":"field1"}]}',
                    400
                ),
                $this->createJsonRequest('POST', '/', ['field1' => '']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter'),
            ],
            'no error thrown with validation and valid body' => [
                new Response('MyObject:value1'),
                $this->createJsonRequest('POST', '/', ['field1' => 'value1']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter')
                    ->setBodyValidationOptions((new ValidationOptions())->setValidationGroups(['Default'])),
            ],
            'body validation can be disabled' => [
                new Response('MyObject:'),
                $this->createJsonRequest('POST', '/', ['field1' => '']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter')
                    ->disableBodyValidation(),
            ],
            'validation groups work' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"Custom message from another group","field":"field1"}]}',
                    400
                ),
                $this->createJsonRequest('POST', '/', ['field1' => '']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter')
                    ->setBodyValidationOptions((new ValidationOptions())->setValidationGroups(['another'])),
            ],
            'validation property paths can be configured' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"This value should not be blank.","field":"my.field"}]}',
                    400
                ),
                $this->createJsonRequest('POST', '/', ['field1' => '']),
                (new RestRequestOptions())
                    ->setBodyDenormalizationType(MyObject::class)
                    ->setBodyParameterName('parameter')
                    ->setBodyValidationOptions((new ValidationOptions())->setViolationPathMap(['field1' => 'my.field'])),
            ],

            'response normalization is used' => [
                new Response('{"field1":"field from controller"}'),
                $this->createRequest('POST', '/with-return'),
                (new RestRequestOptions()),
            ],
            'response normalization type can be overwritten' => [
                new Response('{"field1_custom":"field from controller"}'),
                $this->createRequest('POST', '/with-return'),
                (new RestRequestOptions())->setResponseNormalizationType('my_object_custom'),
            ],

            'path attribute resolver works' => [
                new Response('prefixed_url-parameter'),
                $this->createRequest('POST', '/with-url-parameter/url-parameter'),
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('urlParameter')
                ),
            ],

            'several path attribute resolvers work' => [
                new Response('prefixed_111 prefixed_222'),
                $this->createRequest('POST', '/with-url-parameters/111/222'),
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter1')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('urlParameter1')
                )
                    ->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter2')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('urlParameter2')
                ),
            ],
            'path attribute resolvers can be optional' => [
                new Response('prefixed_111'),
                $this->createRequest('POST', '/with-url-parameter/111'),
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('urlParameter')
                )
                    ->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('unusedArgumentName')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('unusedParameter')
                    ->setResolutionMandatory(false)
                ),
            ],
            'unresolved path attributes rgives 404 error' => [
                new Response('{"error":"not_found","error_description":"Resource was not found"}', 404),
                $this->createRequest('POST', '/with-url-parameter/111'),
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter')
                    ->setDenormalizationType('prefixed')
                    ->setPathPartName('nonExistentUrlParameter')
                ),
            ],
            'path attribute resolvers work with objects' => [
                new Response('MyObject:111'),
                $this->createRequest('POST', '/with-url-parameter/111'),
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                    ->setParameterName('parameter')
                    ->setDenormalizationType('my_object_resolver')
                    ->setPathPartName('urlParameter')
                ),
            ],

            'query attribute resolvers work' => [
                new Response('MyObject:a MyObject:b'),
                $this->createRequest('POST', '/with-parameters?field1=a&field1_custom=b'),
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter1')
                    ->setDenormalizationType(MyObject::class)
                )
                    ->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter2')
                    ->setDenormalizationType('my_object_custom')
                ),
            ],
            'query attribute resolver with multiple level objects work' => [
                new Response('MyObject:a.b'),
                $this->createRequest('POST', '/?field1=a.&internal[field1]=b'),
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter')
                    ->setDenormalizationType(MyObject::class)
                ),
            ],

            'query attribute resolvers validate entities by default' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"This value should not be blank.","field":"field1"}]}',
                    400
                ),
                $this->createRequest('POST', '/with-parameters?field1=a&field1_custom='),
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter1')
                    ->setDenormalizationType(MyObject::class)
                )
                    ->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter2')
                    ->setDenormalizationType('my_object_custom')
                ),
            ],

            'query attribute resolvers validation groups and property paths work' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"Custom message from another group","field":"field1_custom"}]}',
                    400
                ),
                $this->createRequest('POST', '/with-parameters?field1=a&field1_custom='),
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter1')
                    ->setDenormalizationType(MyObject::class)
                )
                    ->addQueryResolverOptions(
                    (new QueryResolverOptions())
                    ->setParameterName('parameter2')
                    ->setDenormalizationType('my_object_custom')
                    ->setValidationOptions(
                            (new ValidationOptions())
                        ->setValidationGroups(['another'])
                        ->setViolationPathMap(['field1' => 'field1_custom'])
                    )
                ),
            ],

            'required permissions are checked with no authentication' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found"}',
                    401
                ),
                $this->createRequest('POST', '/'),
                (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']),
            ],
            'required permissions are checked with no enough permissions' => [
                new Response(
                    '{"error":"forbidden","error_description":"Access to this API endpoint is forbidden for current client"}',
                    403
                ),
                $this->createRequest('POST', '/', '', [], 'user'),
                (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']),
            ],
            'required permissions passes when user has specified permissions' => [
                new Response('default'),
                $this->createRequest('POST', '/', '', [], 'admin'),
                (new RestRequestOptions())->setRequiredPermissions(['ROLE_ADMIN']),

            ],
        ];
    }
}
