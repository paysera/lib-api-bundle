<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller\Attribute;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\BodyContentType;
use Paysera\Bundle\ApiBundle\Attribute\PathAttribute;
use Paysera\Bundle\ApiBundle\Attribute\Query;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Attribute\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Attribute\Validation;
use Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity\MyObject;
use Paysera\Pagination\Entity\Pager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AttributedController
{
    #[Route(path: '/attributed/testBodyNormalizationWithExtractedKeyValue', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'keyValueInBody', denormalizationType: 'extract:key')]
    public function testBodyNormalizationWithExtractedKeyValue(string $keyValueInBody = 'default'): Response
    {
        return new Response($keyValueInBody);
    }

    #[Route(path: '/attributed/testBodyNormalizationWithDenormalizationGroup', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'keyValueInBody', denormalizationType: 'extract:key', denormalizationGroup: 'custom')]
    public function testBodyNormalizationWithDenormalizationGroup(string $keyValueInBody = 'default'): Response
    {
        return new Response($keyValueInBody);
    }

    #[Route(path: '/attributed/testBodyNormalizationWithRequiredBody', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'body', denormalizationType: 'extract:key')]
    public function testBodyNormalizationWithRequiredBody(string $body): Response
    {
        // should fail as we don't pass any body
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testBodyAndResponseNormalization', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'resource')]
    public function testBodyAndResponseNormalization(MyObject $resource): MyObject
    {
        return $resource;
    }

    #[Route(path: '/attributed/testBodyNormalizationWithCustomContentType', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'body', denormalizationType: 'prefixed')]
    #[BodyContentType(supportedContentTypes: ['text/plain'])]
    public function testBodyNormalizationWithCustomContentType(string $body): Response
    {
        return new Response($body);
    }

    #[Route(path: '/attributed/testBodyNormalizationWithCustomContentTypeAndJsonDecode', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'keyValueInBody', denormalizationType: 'extract:key')]
    #[BodyContentType(supportedContentTypes: ['text/plain'], jsonEncodedBody: true)]
    public function testBodyNormalizationWithCustomContentTypeAndJsonDecode(string $keyValueInBody): Response
    {
        return new Response($keyValueInBody);
    }

    #[Route(path: '/attributed/testBodyNormalizationWithSemiContentTypeRestriction', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'body', denormalizationType: 'prefixed')]
    #[BodyContentType(supportedContentTypes: ['image/jpeg', 'text/*'])]
    public function testBodyNormalizationWithSemiContentTypeRestriction(string $body): Response
    {
        return new Response($body);
    }

    #[Route(path: '/attributed/testBodyNormalizationWithValidation', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'resource')]
    #[Validation(groups: ['field1_email'], violationPathMap: ['field1' => 'my_mapped_key'])]
    public function testBodyNormalizationWithValidation(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testBodyNormalizationWithInnerTypeValidation', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'resource')]
    #[Validation(groups: ['internal_field1_email'])]
    public function testBodyNormalizationWithInnerTypeValidation(MyObject $resource): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testBodyValidationCanBeTurnedOff', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'resource')]
    #[Validation(enabled: false)]
    public function testBodyValidationCanBeTurnedOff(MyObject $resource): Response
    {
        return new Response('OK');
    }

    #[Route(path: '/attributed/testBodyValidationCanBeTurnedOffWithEmptyGroups', methods: Request::METHOD_POST)]
    #[Body(parameterName: 'resource')]
    #[Validation(groups: [])]
    public function testBodyValidationCanBeTurnedOffWithEmptyGroups(MyObject $resource): Response
    {
        return new Response('OK');
    }

    #[Route(path: '/attributed/testPathAttribute/{id}', methods: Request::METHOD_GET)]
    #[Route(path: '/attributed/testPathAttribute', methods: Request::METHOD_GET)]
    #[PathAttribute(parameterName: 'parameter', pathPartName: 'id', resolverType: 'prefixed')]
    public function testPathAttribute(string $parameter = 'default'): Response
    {
        return new Response($parameter);
    }

    #[Route(path: '/attributed/testPathAttributeWithFindingObject/{id}', methods: Request::METHOD_GET)]
    #[PathAttribute(parameterName: 'myObject', pathPartName: 'id')]
    public function testPathAttributeWithFindingObject(MyObject $myObject): Response
    {
        return new Response($myObject->getField1());
    }

    #[Route(path: '/attributed/testPathAttributeWithFailedResolution/{id}', methods: Request::METHOD_GET)]
    #[PathAttribute(parameterName: 'myObject', pathPartName: 'id', resolverType: 'always_null')]
    public function testPathAttributeWithFailedResolution(MyObject $myObject): Response
    {
        // should fail before calling controller
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testQueryResolver', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'parameter', denormalizationType: 'extract:parameter')]
    public function testQueryResolver(string $parameter): Response
    {
        return new Response($parameter);
    }

    #[Route(path: '/attributed/testQueryResolverWithDenormalizationGroup', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'parameter', denormalizationType: 'extract:parameter', denormalizationGroup: 'custom')]
    public function testQueryResolverWithDenormalizationGroup(string $parameter): Response
    {
        return new Response($parameter);
    }

    #[Route(path: '//attributed/testQueryResolverPagerLimitIs42', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'pager')]
    public function testQueryResolverPagerLimitIs42(Pager $pager): Response
    {
        return new Response($pager->getLimit() === 42 ? 'OK' : 'FAIL');
    }

    #[Route(path: '/attributed/testQueryResolverHasDefaultValidation', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'myObject')]
    public function testQueryResolverHasDefaultValidation(MyObject $myObject): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testQueryResolverCanTurnOffValidation', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'myObject', validation: new Validation(enabled: false))]
    public function testQueryResolverCanTurnOffValidation(MyObject $myObject): Response
    {
        return new Response('OK');
    }

    #[Route(path: '/attributed/testQueryResolverCanTurnOffValidationWithEmptyGroups', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'myObject', validation: new Validation(groups: []))]
    public function testQueryResolverCanTurnOffValidationWithEmptyGroups(MyObject $myObject): Response
    {
        return new Response('OK');
    }

    #[Route(path: '/attributed/testQueryResolverValidationWithInvalidData', methods: Request::METHOD_GET)]
    #[Query(parameterName: 'myObject', validation: new Validation(groups: ['field1_email'], violationPathMap: ['field1' => 'mapped_key']))]
    public function testQueryResolverValidationWithInvalidData(MyObject $myObject): Response
    {
        // should fail validation
        return new Response('FAIL');
    }

    #[Route(path: '/attributed/testRequiredPermissions', methods: Request::METHOD_GET)]
    #[RequiredPermissions(permissions: ['ROLE_USER', 'ROLE_ADMIN'])]
    public function testRequiredPermissions(): Response
    {
        return new Response('OK');
    }

    #[Route(path: '/attributed/testResponseNormalization', methods: Request::METHOD_GET)]
    #[ResponseNormalization(normalizationType: 'my_object_custom')]
    public function testResponseNormalization(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }

    #[Route(path: '/attributed/testResponseNormalizationWithNormalizationGroup', methods: Request::METHOD_GET)]
    #[ResponseNormalization(normalizationGroup: 'custom')]
    public function testResponseNormalizationWithNormalizationGroup(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }

    #[Route(path: '/attributed/testResponseNormalizationWithGuessedNormalizer', methods: Request::METHOD_GET)]
    #[ResponseNormalization]
    public function testResponseNormalizationWithGuessedNormalizer(): MyObject
    {
        return (new MyObject())
            ->setField1('hi')
        ;
    }
}
