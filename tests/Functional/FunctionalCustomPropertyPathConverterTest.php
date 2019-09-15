<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;

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
            '{"error":"invalid_parameters","error_description":"Some required parameter is missing or it\'s format is invalid","errors":[{"code":"is_blank","message":"This value should not be blank.","field":"prefixed:field1"}]}',
            $response->getContent()
        );
    }
}
