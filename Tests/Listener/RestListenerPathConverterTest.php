<?php

namespace Paysera\Bundle\RestBundle\Tests;

use Paysera\Bundle\RestBundle\ApiManager;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Paysera\Bundle\RestBundle\Listener\RestListener;
use Paysera\Bundle\RestBundle\Normalizer\ErrorNormalizer;
use Paysera\Bundle\RestBundle\Normalizer\NameAwareDenormalizerInterface;
use Paysera\Bundle\RestBundle\RestApi;
use Paysera\Bundle\RestBundle\Service\ExceptionLogger;
use Paysera\Bundle\RestBundle\Service\FormatDetector;
use Paysera\Bundle\RestBundle\Service\ParameterToEntityMapBuilder;
use Paysera\Bundle\RestBundle\Service\RequestLogger;
use Paysera\Component\Serializer\Converter\CamelCaseToSnakeCaseConverter;
use Paysera\Component\Serializer\Converter\NoOpConverter;
use Paysera\Component\Serializer\Encoding\Json;
use Paysera\Component\Serializer\Entity\Violation;
use Paysera\Component\Serializer\Factory\ContextAwareNormalizerFactory;
use Paysera\Component\Serializer\Normalizer\ArrayNormalizer;
use Paysera\Component\Serializer\Normalizer\ViolationNormalizer;
use Paysera\Component\Serializer\Validation\PropertyPathConverterInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RestListenerPathConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Mockery\MockInterface|FilterControllerEvent */
    private $filterControllerEvent;

    public function setUp()
    {
        $this->filterControllerEvent = \Mockery::mock(FilterControllerEvent::class);
    }

    public function testOnKernelControllerWithRequestQueryMapperValidationThrowsExceptionWithCamelCasePathConverter()
    {
        $exceptionThrowed = false;
        try {
            $this
                ->createRestListener(new CamelCaseToSnakeCaseConverter())
                ->onKernelController($this->filterControllerEvent)
            ;
        } catch (ApiException $apiException) {
            $exceptionThrowed = true;
            $this->assertEquals(
                [
                    'first_name' => ['firstName message'],
                    'last_name' => ['lastName message'],
                ],
                $apiException->getProperties()
            );

            $this->assertEquals(
                [
                    (new Violation())->setField('first_name')->setMessage('firstName message'),
                    (new Violation())->setField('last_name')->setMessage('lastName message'),
                ],
                $apiException->getViolations()
            );
        }

        $this->assertTrue($exceptionThrowed);
    }

    public function testOnKernelControllerWithRequestQueryMapperValidationThrowsExceptionWithNoOpConverter()
    {
        $exceptionThrowed = false;
        try {
            $this->createRestListener(new NoOpConverter())->onKernelController($this->filterControllerEvent);
        } catch (ApiException $apiException) {
            $exceptionThrowed = true;
            $this->assertEquals(
                [
                    'firstName' => ['firstName message'],
                    'last_name' => ['lastName message'],
                ],
                $apiException->getProperties()
            );

            $this->assertEquals(
                [
                    (new Violation())->setField('firstName')->setMessage('firstName message'),
                    (new Violation())->setField('last_name')->setMessage('lastName message'),
                ],
                $apiException->getViolations()
            );
        }

        $this->assertTrue($exceptionThrowed);
    }

    /**
     * @return RestListener
     */
    private function createRestListener(PropertyPathConverterInterface $pathConverter)
    {
        $parameterBag = new ParameterBag();
        $queryParameterBag = new ParameterBag();

        $entity = [
            'firstName' => 1,
            'last_name' => 2,
        ];

        $parameterBag->add($entity);
        $parameterBag->set('api_key', 'api');

        $queryParameterBag->add($entity);

        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('getContent')->andReturn('{}');
        $request->attributes = $parameterBag;
        $request->query = $queryParameterBag;

        $this->filterControllerEvent->shouldReceive('getRequest')->andReturn($request);

        if (interface_exists('Symfony\Component\Validator\Validator\ValidatorInterface')) {
            $validator = \Mockery::mock(ValidatorInterface::class);
        } else {
            $validator = \Mockery::mock(LegacyValidatorInterface::class);
        }

        $violationList = new ConstraintViolationList([
            new ConstraintViolation('firstName message', '', [], '', 'firstName', '1'),
            new ConstraintViolation('lastName message', '', [], '', 'last_name', '2'),
        ]);

        $validator->shouldReceive('validate')->andReturn($violationList);

        $formatDetector = \Mockery::mock(FormatDetector::class);
        $formatDetector->shouldReceive('getRequestFormat')->andReturn('json');

        $requestMapper = \Mockery::mock(NameAwareDenormalizerInterface::class);
        $requestMapper->shouldReceive('mapToEntity')->andReturn([]);
        $requestMapper->shouldReceive('getName')->andReturn('name');

        $container = \Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->andReturn($requestMapper);

        $api = new RestApi($container, new NullLogger());
        $api->dontLogRequest('controller');
        $api->addRequestMapper('api', 'controller', 'property');
        $api->setPropertyPathConverter($pathConverter);

        $apiManager = new ApiManager(
            $formatDetector,
            new NullLogger(),
            $validator,
            new ErrorNormalizer(
                new ArrayNormalizer(new ViolationNormalizer()),
                new ArrayNormalizer(new ViolationNormalizer())
            )
        );

        $apiManager->addApiByKey($api, 'api');
        $apiManager->addDecoder(new Json(), 'json');

        $parameterToEntityMapBuilder = \Mockery::mock(ParameterToEntityMapBuilder::class);
        $parameterToEntityMapBuilder->shouldReceive('buildParameterToEntityMap')->andReturn([]);

        return new RestListener(
            $apiManager,
            \Mockery::mock(ContextAwareNormalizerFactory::class),
            new NullLogger(),
            $parameterToEntityMapBuilder,
            new RequestLogger(new NullLogger()),
            new ExceptionLogger()
        );
    }
}
