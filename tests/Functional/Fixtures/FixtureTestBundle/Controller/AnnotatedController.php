<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\BodyContentType;
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute;
use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Pagination\Entity\Pager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnnotatedController
{
    /**
     * @Route(path="/annotated/testBodyNormalizationWithExtractedKeyValue", methods={"POST"})
     *
     * @Body(parameterName="keyValueInBody", denormalizationType="extract:key")
     */
    public function testBodyNormalizationWithExtractedKeyValue(string $keyValueInBody = 'default'): Response
    {
        return new Response($keyValueInBody);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithDenormalizationGroup", methods={"POST"})
     *
     * @Body(parameterName="keyValueInBody", denormalizationType="extract:key", denormalizationGroup="custom")
     */
    public function testBodyNormalizationWithDenormalizationGroup(string $keyValueInBody = 'default'): Response
    {
        return new Response($keyValueInBody);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithRequiredBody", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="extract:key")
     */
    public function testBodyNormalizationWithRequiredBody(string $body): Response
    {
        // should fail as we don't pass any body
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyAndResponseNormalization", methods={"POST"})
     *
     * @Body(parameterName="resource")
     */
    public function testBodyAndResponseNormalization(MyObject $resource): MyObject
    {
        return $resource;
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithCustomContentType", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="prefixed")
     * @BodyContentType(supportedContentTypes={"text/plain"})
     */
    public function testBodyNormalizationWithCustomContentType(string $body): Response
    {
        return new Response($body);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithCustomContentTypeAndJsonDecode", methods={"POST"})
     *
     * @Body(parameterName="keyValueInBody", denormalizationType="extract:key")
     * @BodyContentType(supportedContentTypes={"text/plain"}, jsonEncodedBody=true)
     */
    public function testBodyNormalizationWithCustomContentTypeAndJsonDecode(string $keyValueInBody): Response
    {
        return new Response($keyValueInBody);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithSemiContentTypeRestriction", methods={"POST"})
     *
     * @Body(parameterName="body", denormalizationType="prefixed")
     * @BodyContentType(supportedContentTypes={"image/jpeg", "text/*"})
     */
    public function testBodyNormalizationWithSemiContentTypeRestriction(string $body): Response
    {
        return new Response($body);
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithValidation", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={"field1_email"}, violationPathMap={"field1": "my_mapped_key"})
     */
    public function testBodyNormalizationWithValidation(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyNormalizationWithInnerTypeValidation", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={"internal_field1_email"})
     */
    public function testBodyNormalizationWithInnerTypeValidation(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testBodyValidationCanBeTurnedOff", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(enabled=false)
     */
    public function testBodyValidationCanBeTurnedOff(MyObject $resource): Response
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testBodyValidationCanBeTurnedOffWithEmptyGroups", methods={"POST"})
     *
     * @Body(parameterName="resource")
     * @Validation(groups={})
     */
    public function testBodyValidationCanBeTurnedOffWithEmptyGroups(MyObject $resource): Response
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testPathAttribute/{id}", methods={"GET"})
     * @Route(path="/annotated/testPathAttribute", methods={"GET"})
     *
     * @PathAttribute(parameterName="parameter", pathPartName="id", resolverType="prefixed")
     */
    public function testPathAttribute(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    /**
     * @Route(path="/annotated/testPathAttributeWithFindingObject/{id}", methods={"GET"})
     *
     * @PathAttribute(parameterName="myObject", pathPartName="id")
     */
    public function testPathAttributeWithFindingObject(MyObject $myObject): Response
    {
        return new Response($myObject->getField1());
    }

    /**
     * @Route(path="/annotated/testPathAttributeWithFailedResolution/{id}", methods={"GET"})
     *
     * @PathAttribute(parameterName="myObject", pathPartName="id", resolverType="always_null")
     */
    public function testPathAttributeWithFailedResolution(MyObject $myObject): Response
    {
        // should fail before calling controller
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolver", methods={"GET"})
     *
     * @Query(parameterName="parameter", denormalizationType="extract:parameter")
     */
    public function testQueryResolver(string $parameter): Response
    {
        return new Response($parameter);
    }

    /**
     * @Route(path="/annotated/testQueryResolverWithDenormalizationGroup", methods={"GET"})
     *
     * @Query(parameterName="parameter", denormalizationType="extract:parameter", denormalizationGroup="custom")
     */
    public function testQueryResolverWithDenormalizationGroup(string $parameter): Response
    {
        return new Response($parameter);
    }

    /**
     * @Route(path="/annotated/testQueryResolverPagerLimitIs42", methods={"GET"})
     *
     * @Query(parameterName="pager")
     */
    public function testQueryResolverPagerLimitIs42(Pager $pager): Response
    {
        return new Response($pager->getLimit() === 42 ? 'OK' : 'FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolverHasDefaultValidation", methods={"GET"})
     *
     * @Query(parameterName="myObject")
     */
    public function testQueryResolverHasDefaultValidation(MyObject $myObject): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testQueryResolverCanTurnOffValidation", methods={"GET"})
     *
     * @Query(parameterName="myObject", validation=@Validation(enabled=false))
     */
    public function testQueryResolverCanTurnOffValidation(MyObject $myObject): Response
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testQueryResolverCanTurnOffValidationWithEmptyGroups", methods={"GET"})
     *
     * @Query(parameterName="myObject", validation=@Validation(groups={}))
     */
    public function testQueryResolverCanTurnOffValidationWithEmptyGroups(MyObject $myObject): Response
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
     */
    public function testQueryResolverValidationWithInvalidData(MyObject $myObject): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    /**
     * @Route(path="/annotated/testRequiredPermissions", methods={"GET"})
     *
     * @RequiredPermissions(permissions={"ROLE_USER", "ROLE_ADMIN"})
     */
    public function testRequiredPermissions(): Response
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/annotated/testResponseNormalization", methods={"GET"})
     *
     * @ResponseNormalization(normalizationType="my_object_custom")
     */
    public function testResponseNormalization(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }

    /**
     * @Route(path="/annotated/testResponseNormalizationWithNormalizationGroup", methods={"GET"})
     *
     * @ResponseNormalization(normalizationGroup="custom")
     */
    public function testResponseNormalizationWithNormalizationGroup(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }

    /**
     * @Route(path="/annotated/testResponseNormalizationWithGuessedNormalizer", methods={"GET"})
     *
     * @ResponseNormalization()
     */
    public function testResponseNormalizationWithGuessedNormalizer(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }
}
