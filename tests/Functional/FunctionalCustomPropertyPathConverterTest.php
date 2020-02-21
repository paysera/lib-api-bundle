<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;

class FunctionalCustomPropertyPathConverterTest extends FunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->setUpContainer('custom_converter');
    }

    public function testRestRequestWithCustomPathConverter()
    {
        $options = (new RestRequestOptions())
            ->setBodyDenormalizationType(MyObject::class)
            ->setBodyParameterName('parameter')
        ;
        $request = $this->createJsonRequest('POST', '/', ['field1' => '']);

        $this->kernel->getContainer()->get('rest_registry')->registerRestRequestOptions(
            $options,
            $this->kernel->getContainer()->get('router')->match($request->getPathInfo())['_controller']
        );

        $response = $this->handleRequest($request);

        $this->assertEquals(
            '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","error_uri":null,"error_properties":null,"error_data":null,"errors":[{"code":"is_blank","message":"This value should not be blank.","field":"prefixed:field1"}]}',
            $response->getContent()
        );
    }
}
