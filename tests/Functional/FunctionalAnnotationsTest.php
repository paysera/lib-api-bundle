<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FunctionalAnnotationsTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpContainer('basic');
    }

    /**
     * @dataProvider restRequestsConfigurationProvider
     * @param Response $expectedResponse
     * @param Request $request
     * @param Response|null $extraResponseVersion
     */
    public function testAnnotatedRestRequestConfiguration(
        Response $expectedResponse,
        Request $request,
        Response $extraResponseVersion = null
    ) {
        $this->makeTest('annotated', $expectedResponse, $request, $extraResponseVersion);
    }

    /**
     * @dataProvider restRequestsConfigurationProvider
     * @param Response $expectedResponse
     * @param Request $request
     * @param Response|null $extraResponseVersion
     */
    public function testAttributedRestRequestConfiguration(
        Response $expectedResponse,
        Request $request,
        Response $extraResponseVersion = null
    ) {
        $this->makeTest('attributed', $expectedResponse, $request, $extraResponseVersion);
    }

    private function makeTest(
        string $pathPrefix,
        Response $expectedResponse,
        Request $request,
        Response $extraResponseVersion = null
    ): void {
        if ($pathPrefix === 'attributed') {
            $this->checkAttributeConfigurationSupport();
        }

        $request->server->set(
            'REQUEST_URI',
            sprintf('/%s%s', $pathPrefix, $request->server->get('REQUEST_URI'))
        );
        $response = $this->handleRequest($request);
        $assertionMessage = 'expected correct content';

        if ($extraResponseVersion === null) {
            $this->assertEquals(
                $expectedResponse->getContent(),
                $response->getContent(),
                $assertionMessage
            );
        } else {
            $this->assertThat(
                $response->getContent(),
                $this->logicalOr(
                    $this->equalTo($expectedResponse->getContent()),
                    $this->equalTo($extraResponseVersion->getContent())
                ),
                $assertionMessage
            );
        }

        $this->assertEquals(
            $expectedResponse->getStatusCode(),
            $response->getStatusCode(),
            'expected correct status code'
        );
    }

    public function restRequestsConfigurationProvider(): array
    {
        return [
            'testBodyNormalizationWithExtractedKeyValue' => [
                new Response('this_should_be_extracted'),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyNormalizationWithExtractedKeyValue',
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
                    '/testBodyNormalizationWithExtractedKeyValue'
                ),
            ],
            'testBodyNormalizationWithDenormalizationGroup' => [
                new Response('custom'),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyNormalizationWithDenormalizationGroup',
                    [
                        'key_custom' => 'custom',
                        'key' => 'wrong_key',
                    ]
                ),
            ],
            'test default content types validation' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (text/plain) is not supported"}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithExtractedKeyValue',
                    'something',
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithRequiredBody' => [
                new Response(
                    '{"error":"invalid_request","error_description":"Expected non-empty request body"}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithRequiredBody'
                ),
            ],
            'testBodyAndResponseNormalization' => [
                new Response('{"field1":"value1"}'),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyAndResponseNormalization',
                    ['field1' => 'value1']
                ),
            ],
            'testBodyAndResponseNormalization with invalid structure' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Expected string but got integer for key \"internal.field1\""}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyAndResponseNormalization',
                    ['internal' => ['field1' => 1]]
                ),
            ],
            'testBodyNormalizationWithCustomContentType and JSON' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (application/json) is not supported"}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyNormalizationWithCustomContentType',
                    ['key' => 'value']
                ),
            ],
            'testBodyNormalizationWithCustomContentType' => [
                new Response('prefixed_my_text'),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithCustomContentType',
                    'my_text',
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithCustomContentTypeAndJsonDecode and JSON content-type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (application/json) is not supported"}',
                    400
                ),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyNormalizationWithCustomContentTypeAndJsonDecode',
                    ['key' => 'value']
                ),
            ],
            'testBodyNormalizationWithCustomContentTypeAndJsonDecode' => [
                new Response('value'),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithCustomContentTypeAndJsonDecode',
                    json_encode(['key' => 'value']),
                    ['Content-Type' => 'text/plain']
                ),
            ],
            'testBodyNormalizationWithSemiContentTypeRestriction' => [
                new Response('prefixed_by_body'),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithSemiContentTypeRestriction',
                    'by_body',
                    ['Content-Type' => 'text/something']
                ),
            ],
            'testBodyNormalizationWithSemiContentTypeRestriction and invalid content type' => [
                new Response(
                    '{"error":"invalid_request","error_description":"This Content-Type (image/gif) is not supported"}',
                    400
                ),
                $this->createRequest(
                    'POST',
                    '/testBodyNormalizationWithSemiContentTypeRestriction',
                    'by_body',
                    ['Content-Type' => 'image/gif']
                ),
            ],
            'testBodyNormalizationWithValidation' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
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
                    '/testBodyNormalizationWithValidation',
                    ['field1' => 'not an email']
                ),
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'errors' => [
                            [
                                'code' => 'invalid_format',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'my_mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
            ],
            'testBodyNormalizationWithInnerTypeValidation - should convert to snake case' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
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
                    '/testBodyNormalizationWithInnerTypeValidation',
                    ['field1' => 'blah', 'internal' => ['field1' => 'not an email']]
                ),
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'errors' => [
                            [
                                'code' => 'invalid_format',
                                'message' => 'Custom message',
                                'field' => 'internal_field1', // <-- this is converted from internalField1
                            ],
                        ],
                    ]),
                    400
                ),
            ],
            'testBodyValidationCanBeTurnedOff' => [
                new Response('OK', 200),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyValidationCanBeTurnedOff',
                    ['field1' => '']
                ),
            ],
            'testBodyValidationCanBeTurnedOffWithEmptyGroups' => [
                new Response('OK', 200),
                $this->createJsonRequest(
                    'POST',
                    '/testBodyValidationCanBeTurnedOffWithEmptyGroups',
                    ['field1' => '']
                ),
            ],
            'testPathAttribute' => [
                new Response('prefixed_123'),
                $this->createRequest(
                    'GET',
                    '/testPathAttribute/123'
                ),
            ],
            'testPathAttribute with optional resolution' => [
                new Response('default'),
                $this->createRequest(
                    'GET',
                    '/testPathAttribute'
                ),
            ],
            'testPathAttributeWithFindingObject' => [
                new Response('123'),
                $this->createRequest(
                    'GET',
                    '/testPathAttributeWithFindingObject/123'
                ),
            ],
            'testPathAttributeWithFailedResolution' => [
                new Response('{"error":"not_found","error_description":"Resource was not found"}', 404),
                $this->createRequest(
                    'GET',
                    '/testPathAttributeWithFailedResolution/{id}'
                ),
            ],
            'testQueryResolver' => [
                new Response('my_param'),
                $this->createRequest(
                    'GET',
                    '/testQueryResolver?parameter=my_param'
                ),
            ],
            'testQueryResolver is always mandatory' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Missing required key \"parameter\""}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/testQueryResolver?other_parameter=my_param'
                ),
            ],
            'testQueryResolverWithDenormalizationGroup' => [
                new Response('custom'),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverWithDenormalizationGroup?parameter=wrong_key&parameter_custom=custom'
                ),
            ],
            'testQueryResolverPagerLimitIs42' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverPagerLimitIs42?limit=42'
                ),
            ],
            'testQueryResolverHasDefaultValidation' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"This value should not be blank.","field":"field1"}]}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverHasDefaultValidation?field1='
                ),
            ],
            'testQueryResolverCanTurnOffValidation' => [
                new Response(
                    'OK',
                    200
                ),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverCanTurnOffValidation?field1='
                ),
            ],
            'testQueryResolverCanTurnOffValidationWithEmptyGroups' => [
                new Response(
                    'OK',
                    200
                ),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverCanTurnOffValidationWithEmptyGroups?field1='
                ),
            ],
            'testQueryResolverValidationWithInvalidData - normalizer errors do not map fields' => [
                new Response(
                    '{"error":"invalid_parameters","error_description":"Missing required key \"field1\""}',
                    400
                ),
                $this->createRequest(
                    'GET',
                    '/testQueryResolverValidationWithInvalidData'
                ),
            ],
            'testQueryResolverValidationWithInvalidData' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
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
                    '/testQueryResolverValidationWithInvalidData?field1=not_an_email'
                ),
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'errors' => [
                            [
                                'code' => 'invalid_format',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
            ],
            'testRequiredPermissions without auth' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found"}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/testRequiredPermissions'
                ),
            ],
            'testRequiredPermissions without not enough permissions' => [
                new Response(
                    '{"error":"forbidden","error_description":"Access to this API endpoint is forbidden for current client"}',
                    403
                ),
                $this->createRequest(
                    'GET',
                    '/testRequiredPermissions',
                    null,
                    [],
                    'user'
                ),
            ],
            'testRequiredPermissions with all needed permissions' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/testRequiredPermissions',
                    null,
                    [],
                    'admin'
                ),
            ],
            'testRequiredPermissions with class annotation and with no auth' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found"}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/class/testRequiredPermissions'
                ),
            ],
            'testRequiredPermissions with class annotation and with not enough permissions' => [
                new Response(
                    '{"error":"forbidden","error_description":"Access to this API endpoint is forbidden for current client"}',
                    403
                ),
                $this->createRequest(
                    'GET',
                    '/class/testRequiredPermissions',
                    null,
                    [],
                    'user'
                ),
            ],
            'testRequiredPermissions with class annotation and with all needed permissions' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/class/testRequiredPermissions',
                    null,
                    [],
                    'admin'
                ),
            ],
            'testRequiredPermissions with class annotation and REST-specific method annotations' => [
                new Response(
                    '{"error":"unauthorized","error_description":"This API endpoint requires authentication, none found"}',
                    401
                ),
                $this->createRequest(
                    'GET',
                    '/class/simpleAction'
                ),
            ],
            'testRequiredPermissions with class annotation and REST-specific method annotations: OK' => [
                new Response('OK'),
                $this->createRequest(
                    'GET',
                    '/class/simpleAction',
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
                    '/class/testValidation',
                    ['field1' => 'not an email', 'internal' => ['field1' => 'also not an email']]
                ),
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'errors' => [
                            [
                                'code' => 'invalid_format',
                                'message' => 'This value is not a valid email address.',
                                'field' => 'my_mapped_key',
                            ],
                        ],
                    ]),
                    400
                ),
            ],
            'testValidation with class annotation' => [
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
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
                    '/class/testValidationFromClass',
                    ['field1' => 'not an email', 'internal' => ['field1' => 'also not an email']]
                ),
                new Response(
                    json_encode([
                        'error' => 'invalid_parameters',
                        'error_description' => 'Some required parameter is missing or it\'s format is invalid',
                        'errors' => [
                            [
                                'code' => 'invalid_format',
                                'message' => 'Custom message',
                                'field' => 'internal.field1',
                            ],
                        ],
                    ]),
                    400
                ),
            ],
            'testResponseNormalization' => [
                new Response('{"field1_custom":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/testResponseNormalization'
                ),
            ],
            'testResponseNormalizationWithNormalizationGroup' => [
                new Response('{"field1_custom":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/testResponseNormalizationWithNormalizationGroup'
                ),
            ],
            'testResponseNormalizationWithGuessedNormalizer' => [
                new Response('{"field1":"hi"}'),
                $this->createRequest(
                    'GET',
                    '/testResponseNormalizationWithGuessedNormalizer'
                ),
            ],
        ];
    }
}
