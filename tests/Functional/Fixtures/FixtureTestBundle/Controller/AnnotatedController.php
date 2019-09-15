<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\RestBundle\Annotation\Body;
use Paysera\Bundle\RestBundle\Annotation\BodyContentType;
use Paysera\Bundle\RestBundle\Annotation\PathAttribute;
use Paysera\Bundle\RestBundle\Annotation\Query;
use Paysera\Bundle\RestBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\RestBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\RestBundle\Annotation\Validation;
use Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Pagination\Entity\Pager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnnotatedController
{
    /**
     * @Route(path="/annotated/testBodyNormalizationWithExtractedKeyValue", methods={"POST"})
     *
     * @Body(parameterName="keyValueInBody", denormalizationType="extract:key")
     *
     * @param string $keyValueInBody
     * @return Response
     */
    public function testBodyNormalizationWithExtractedKeyValue(string $keyValueInBody = 'default')
    {
        return new Response($keyValueInBody);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithRequiredBody", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="extract:key")
     *
     * @param string $body
     * @return Response
     */
    public function testBodyNormalizationWithRequiredBody(string $body)
    {
        // should fail as we don't pass any body
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyAndResponseNormalization", methods={"POST"})
     *
     * @Body(parameterName="resource")
     *
     * @param MyObject $resource
     * @return MyObject
     */
    public function testBodyAndResponseNormalization(MyObject $resource)
    {
        return $resource;
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithCustomContentType", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="prefixed")
     * @BodyContentType(supportedContentTypes={"text/plain"})
     *
     * @param string $body
     * @return Response
     */
    public function testBodyNormalizationWithCustomContentType(string $body)
    {
        return new Response($body);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithCustomContentTypeAndJsonDecode", methods={"POST"})
     *
     * @Body(parameterName="keyValueInBody", denormalizationType="extract:key")
     * @BodyContentType(supportedContentTypes={"text/plain"}, jsonEncodedBody=true)
     *
     * @param string $keyValueInBody
     * @return Response
     */
    public function testBodyNormalizationWithCustomContentTypeAndJsonDecode(string $keyValueInBody)
    {
        return new Response($keyValueInBody);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithSemiContentTypeRestriction", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="prefixed")
     * @BodyContentType(supportedContentTypes={"image/jpeg", "text/*"})
     *
     * @param string $body
     * @return Response
     */
    public function testBodyNormalizationWithSemiContentTypeRestriction(string $body)
    {
        return new Response($body);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithValidation", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={"field1_email"}, violationPathMap={"field1": "my_mapped_key"})
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testBodyNormalizationWithValidation(MyObject $resource)
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithInnerTypeValidation", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={"internal_field1_email"})
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testBodyNormalizationWithInnerTypeValidation(MyObject $resource)
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyValidationCanBeTurnedOff", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(enabled=false)
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testBodyValidationCanBeTurnedOff(MyObject $resource)
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testBodyValidationCanBeTurnedOffWithEmptyGroups", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={})
     *
     * @param MyObject $resource
     * @return Response
     */
    public function testBodyValidationCanBeTurnedOffWithEmptyGroups(MyObject $resource)
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testPathAttribute/{id}", methods={"GET"})
     * @Route(path="/annotated/testPathAttribute", methods={"GET"})
     *
     * @PathAttribute(parameterName="parameter", pathPartName="id", denormalizationType="prefixed")
     *
     * @param string $parameter
     * @return Response
     */
    public function testPathAttribute(string $parameter = 'default')
    {
        return new Response($parameter);
    }

    /**
     * @Route(path="/annotated/testPathAttributeWithFindingObject/{id}", methods={"GET"})
     *
     * @PathAttribute(parameterName="myObject", pathPartName="id")
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testPathAttributeWithFindingObject(MyObject $myObject)
    {
        return new Response($myObject->getField1());
    }

    /**
     * @Route(path="/annotated/testPathAttributeWithFailedResolution/{id}", methods={"GET"})
     *
     * @PathAttribute(parameterName="myObject", pathPartName="id", denormalizationType="always_null")
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testPathAttributeWithFailedResolution(MyObject $myObject)
    {
        // should fail before calling controller
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolver", methods={"GET"})
     *
     * @Query(parameterName="parameter", denormalizationType="extract:parameter")
     *
     * @param string $parameter
     * @return Response
     */
    public function testQueryResolver(string $parameter)
    {
        return new Response($parameter);
    }

    /**
     * @Route(path="/annotated/testQueryResolverPagerLimitIs42", methods={"GET"})
     *
     * @Query(parameterName="pager")
     *
     * @param Pager $pager
     * @return Response
     */
    public function testQueryResolverPagerLimitIs42(Pager $pager)
    {
        return new Response($pager->getLimit() === 42 ? 'OK' : 'FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolverHasDefaultValidation", methods={"GET"})
     *
     * @Query(parameterName="myObject")
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testQueryResolverHasDefaultValidation(MyObject $myObject)
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolverCanTurnOffValidation", methods={"GET"})
     *
     * @Query(parameterName="myObject", validation=@Validation(enabled=false))
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testQueryResolverCanTurnOffValidation(MyObject $myObject)
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testQueryResolverCanTurnOffValidationWithEmptyGroups", methods={"GET"})
     *
     * @Query(parameterName="myObject", validation=@Validation(groups={}))
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testQueryResolverCanTurnOffValidationWithEmptyGroups(MyObject $myObject)
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testQueryResolverValidationWithInvalidData", methods={"GET"})
     *
     * @Query(parameterName="myObject", validation=@Validation(
     *     groups={"field1_email"},
     *     violationPathMap={"field1": "mapped_key"}
     * ))
     *
     * @param MyObject $myObject
     * @return Response
     */
    public function testQueryResolverValidationWithInvalidData(MyObject $myObject)
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testRequiredPermissions", methods={"GET"})
     *
     * @RequiredPermissions(permissions={"ROLE_USER", "ROLE_ADMIN"})
     *
     * @return Response
     */
    public function testRequiredPermissions()
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testResponseNormalization", methods={"GET"})
     *
     * @ResponseNormalization(normalizationType="my_object_custom")
     *
     * @return string
     */
    public function testResponseNormalization()
    {
        return (new MyObject())->setField1('hi');
    }

    /**
     * @Route(path="/annotated/testResponseNormalizationWithGuessedNormalizer", methods={"GET"})
     *
     * @ResponseNormalization()
     *
     * @return string
     */
    public function testResponseNormalizationWithGuessedNormalizer()
    {
        return (new MyObject())->setField1('hi');
    }
}
