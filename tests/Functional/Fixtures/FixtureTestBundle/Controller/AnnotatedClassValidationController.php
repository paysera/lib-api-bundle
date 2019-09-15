<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\RestBundle\Annotation\Body;
use Paysera\Bundle\RestBundle\Annotation\Validation;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Validation(groups={"internal_field1_email"}, violationPathMap={"internalField1": "internal.field1"})
 */
class AnnotatedClassValidationController
{
    /**
     * @Route(path="/annotated/class/testValidation", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={"field1_email"}, violationPathMap={"field1": "my_mapped_key"})
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testValidation(MyObject $resource)
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/class/testValidationFromClass", methods={"POST"})
     *
     * @Body(parameterName="resource")
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testValidationFromClass(MyObject $resource)
    {
        // should fail validation
        return new Response('FAIL');
    }
}
