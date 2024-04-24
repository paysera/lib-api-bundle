<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\Validation;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Validation(groups: ['internal_field1_email'], violationPathMap: ['internalField1' => 'internal.field1'])]
class AttributedClassValidationController
{
    /**
     * @Route(path="/attributed/class/testValidation", methods={"POST"})
     */
    #[Body(parameterName: 'resource')]
    #[Validation(groups: ['field1_email'], violationPathMap: ['field1' => 'my_mapped_key'])]
    public function testValidation(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/attributed/class/testValidationFromClass", methods={"POST"})
     */
    #[Body(parameterName: 'resource')]
    public function testValidationFromClass(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }
}
