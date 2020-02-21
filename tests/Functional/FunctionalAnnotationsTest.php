<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalAnnotationsTest extends FunctionalTestCase
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
            'testBodyNormalizationWithExtractedKeyValue' => [
                new Response('this_should_be_extracted'),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithExtractedKeyValue',
                    [
                        'something' => 'unimportant',
                        'key' => 'this_should_be_extracted',
                    ]
                ),
            ],
            'testBodyNormalizationWithExtractedKeyValue and no body' => [
                new Response('default'),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithExtractedKeyValue'
                ),
            ],
            'testBodyNormalizationWithDenormalizationGroup' => [
                new Response('custom'),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithDenormalizationGroup',
                    [
                        'key_custom' => 'custom',
                        'key' => 'wrong_key',
                    ]
                ),
            ],
            'test default content types validation' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (text/plain) is not supported","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithExtractedKeyValue',
                    'something',
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithRequiredBody' => [
                new Response(
                    '{"error":"invalid_request","error_description":"Expected non-empty request body","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithRequiredBody'
                ),
            ],
            'testBodyAndResponseNormalization' => [
                new Response('{"field1":"value1"}'),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyAndResponseNormalization',
                    ['field1' => 'value1']
                ),
            ],
            'testBodyAndResponseNormalization with invalid structure' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Expected string but got integer for key \"internal.field1\"","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyAndResponseNormalization',
                    ['internal' => ['field1' => 1]]
                ),
            ],
            'testBodyNormalizationWithCustomContentType and JSON' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (application/json) is not supported","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithCustomContentType',
                    ['key' => 'value']
                ),
            ],
            'testBodyNormalizationWithCustomContentType' => [
                new Response('prefixed_my_text'),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithCustomContentType',
                    'my_text',
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithCustomContentTypeAndJsonDecode and JSON content-type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (application/json) is not supported","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithCustomContentTypeAndJsonDecode',
                    ['key' => 'value']
                ),
            ],
            'testBodyNormalizationWithCustomContentTypeAndJsonDecode' => [
                new Response('value'),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithCustomContentTypeAndJsonDecode',
                    json_encode(['key' => 'value']),
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithSemiContentTypeRestriction' => [
                new Response('prefixed_by_body'),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithSemiContentTypeRestriction',
                    'by_body',
                    ['Content-Type' => 'text/something']
                ),
            ],
            'testBodyNormalizationWithSemiContentTypeRestriction and invalid content type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (image/gif) is not supported","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithSemiContentTypeRestriction',
                    'by_body',
                    ['Content-Type' => 'image/gif']
                ),
            ],
            'testBodyNormalizationWithValidation' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'error_uri' => null,
                        'error_properties' => null,
                        'error_data' => null,
                        'errors' => [
                            [
                                'code' => 'strict_check_failed',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'my_mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithValidation',
                    ['field1' => 'not an email']
                ),
            ],
            'testBodyNormalizationWithInnerTypeValidation - should convert to snake case' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'error_uri' => null,
                        'error_properties' => null,
                        'error_data' => null,
                        'errors' => [
                            [
                                'code' => 'strict_check_failed',
                                'message' => 'Custom message',
                                'field' => 'internal_field1', // <-- this is converted from internalField1
                            ],
                        ],
                    ]),
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyNormalizationWithInnerTypeValidation',
                    ['field1' => 'blah', 'internal' => ['field1' => 'not an email']]
                ),
            ],
            'testBodyValidationCanBeTurnedOff' => [
                new Response('OK', 200),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyValidationCanBeTurnedOff',
                    ['field1' => '']
                ),
            ],
            'testBodyValidationCanBeTurnedOffWithEmptyGroups' => [
                new Response('OK', 200),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/testBodyValidationCanBeTurnedOffWithEmptyGroups',
                    ['field1' => '']
                ),
            ],
            'testPathAttribute' => [
                new Response('prefixed_123'),
                $this->createRequest(
                    'GET',
                    '/annotated/testPathAttribute/123'
                ),
            ],
            'testPathAttribute with optional resolution' => [
                new Response('default'),
                $this->createRequest(
                    'GET',
                    '/annotated/testPathAttribute'
                ),
            ],
            'testPathAttributeWithFindingObject' => [
                new Response('123'),
                $this->createRequest(
                    'GET',
                    '/annotated/testPathAttributeWithFindingObject/123'
                ),
            ],
            'testPathAttributeWithFailedResolution' => [
                new Response('{"error":"not_found","error_description":"Resource was not found","error_uri":null,"error_properties":null,"error_data":null,"errors":null}', 404),
                $this->createRequest(
                    'GET',
                    '/annotated/testPathAttributeWithFailedResolution/{id}'
                ),
            ],
            'testQueryResolver' => [
                new Response('my_param'),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolver?parameter=my_param'
                ),
            ],
            'testQueryResolver is always mandatory' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Missing required key \"parameter\"","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolver?other_parameter=my_param'
                ),
            ],
            'testQueryResolverWithDenormalizationGroup' => [
                new Response('custom'),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverWithDenormalizationGroup?parameter=wrong_key&parameter_custom=custom'
                ),
            ],
            'testQueryResolverPagerLimitIs42' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverPagerLimitIs42?limit=42'
                ),
            ],
            'testQueryResolverHasDefaultValidation' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","error_uri":null,"error_properties":null,"error_data":null,"errors":[{"code":"is_blank","message":"This value should not be blank.","field":"field1"}]}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverHasDefaultValidation?field1='
                ),
            ],
            'testQueryResolverCanTurnOffValidation' => [
                new Response(
                    'OK',
                    200
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverCanTurnOffValidation?field1='
                ),
            ],
            'testQueryResolverCanTurnOffValidationWithEmptyGroups' => [
                new Response(
                    'OK',
                    200
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverCanTurnOffValidationWithEmptyGroups?field1='
                ),
            ],
            'testQueryResolverValidationWithInvalidData - normalizer errors do not map fields' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Missing required key \"field1\"","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverValidationWithInvalidData'
                ),
            ],
            'testQueryResolverValidationWithInvalidData' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'error_uri' => null,
                        'error_properties' => null,
                        'error_data' => null,
                        'errors' => [
                            [
                                'code' => 'strict_check_failed',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testQueryResolverValidationWithInvalidData?field1=not_an_email'
                ),
            ],
            'testRequiredPermissions without auth' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testRequiredPermissions'
                ),
            ],
            'testRequiredPermissions without not enough permissions' => [
                new Response(
                    '{"error":"forbidden","error_description":"Access to this API endpoint is forbidden for current client","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    403
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/testRequiredPermissions',
                    null,
                    [],
                    'user'
                ),
            ],
            'testRequiredPermissions with all needed permissions' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/annotated/testRequiredPermissions',
                    null,
                    [],
                    'admin'
                ),
            ],
            'testRequiredPermissions with class annotation and with no auth' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/class/testRequiredPermissions'
                ),
            ],
            'testRequiredPermissions with class annotation and with not enough permissions' => [
                new Response(
                    '{"error":"forbidden","error_description":"Access to this API endpoint is forbidden for current client","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    403
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/class/testRequiredPermissions',
                    null,
                    [],
                    'user'
                ),
            ],
            'testRequiredPermissions with class annotation and with all needed permissions' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/annotated/class/testRequiredPermissions',
                    null,
                    [],
                    'admin'
                ),
            ],
            'testRequiredPermissions with class annotation and REST-specific method annotations' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found","error_uri":null,"error_properties":null,"error_data":null,"errors":null}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/annotated/class/simpleAction'
                ),
            ],
            'testRequiredPermissions with class annotation and REST-specific method annotations: OK' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/annotated/class/simpleAction',
                    null,
                    [],
                    'user'
                ),
            ],
            'testValidation with class annotation - action overwrites class' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'error_uri' => null,
                        'error_properties' => null,
                        'error_data' => null,
                        'errors' => [
                            [
                                'code' => 'strict_check_failed',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'my_mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/class/testValidation',
                    ['field1' => 'not an email', 'internal' => ['field1' => 'also not an email']]
                ),
            ],
            'testValidation with class annotation' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'error_uri' => null,
                        'error_properties' => null,
                        'error_data' => null,
                        'errors' => [
                            [
                                'code' => 'strict_check_failed',
                                'message' => 'Custom message',
                                'field' => 'internal.field1',
                            ],
                        ],
                    ]),
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/annotated/class/testValidationFromClass',
                    ['field1' => 'not an email', 'internal' => ['field1' => 'also not an email']]
                ),
            ],
            'testResponseNormalization' => [
                new Response('{"field1_custom":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/annotated/testResponseNormalization'
                ),
            ],
            'testResponseNormalizationWithNormalizationGroup' => [
                new Response('{"field1_custom":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/annotated/testResponseNormalizationWithNormalizationGroup'
                ),
            ],
            'testResponseNormalizationWithGuessedNormalizer' => [
                new Response('{"field1":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/annotated/testResponseNormalizationWithGuessedNormalizer'
                ),
            ],
        ];
    }
}
