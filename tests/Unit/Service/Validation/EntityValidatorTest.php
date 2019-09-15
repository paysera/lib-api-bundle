<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service\Validation;

use Mockery;
use Paysera\Bundle\RestBundle\Entity\ValidationOptions;
use Paysera\Bundle\RestBundle\Entity\Violation;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Paysera\Bundle\RestBundle\Service\Validation\EntityValidator;
use Paysera\Bundle\RestBundle\Service\Validation\PropertyPathConverterInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use stdClass;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidatorTest extends MockeryTestCase
{

    public function testValidateDoesNotFailWithNonObject()
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $propertyPathConverter = Mockery::mock(PropertyPathConverterInterface::class);

        $validator->shouldNotReceive('validate');

        $entityValidator = new EntityValidator($validator, $propertyPathConverter);
        $entityValidator->validate('string', new ValidationOptions());
    }

    /**
     * @dataProvider provideDataForTestValidate
     *
     * @param ApiException|null $expectedException
     * @param ValidationOptions $validationOptions
     * @param ConstraintViolationInterface[] $violationList
     * @param string[] $groups
     */
    public function testValidate($expectedException, ValidationOptions $validationOptions, $violationList, $groups = [])
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $propertyPathConverter = Mockery::mock(PropertyPathConverterInterface::class);

        $entity = new stdClass();
        $validator
            ->shouldReceive('validate')
            ->with($entity, null, $groups)
            ->andReturn(new ConstraintViolationList($violationList))
        ;
        $propertyPathConverter
            ->shouldReceive('convert')
            ->with('pathToConvert')
            ->andReturn('convertedPath')
        ;
        $propertyPathConverter
            ->shouldReceive('convert')
            ->andReturnUsing(function ($path) {
                return $path;
            })
        ;

        $entityValidator = new EntityValidator($validator, $propertyPathConverter);
        try {
            $entityValidator->validate($entity, $validationOptions);
            if ($expectedException !== null) {
                $this->fail('Expected exception');
            }
        } catch (ApiException $exception) {
            $this->assertEquals($expectedException, $exception);
        }
    }

    public function provideDataForTestValidate()
    {
        return [
            [
                null,
                new ValidationOptions(),
                [],
                ['Default']
            ],
            [
                null,
                (new ValidationOptions())->setValidationGroups(['blah', 'something']),
                [],
                ['blah', 'something']
            ],
            [
                null,
                (new ValidationOptions())->setValidationGroups([]),
                [],
                []
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('path')->setMessage('Message'),
                ]),
                (new ValidationOptions()),
                [new ConstraintViolation(
                    'Message',
                    null,
                    [],
                    null,
                    'path',
                    'value'
                )],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('otherPath')->setMessage('Message'),
                ]),
                (new ValidationOptions())->setViolationPathMap(['path' => 'otherPath']),
                [new ConstraintViolation(
                    'Message',
                    null,
                    [],
                    null,
                    'path',
                    'value'
                )],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('convertedPath')->setMessage('Message'),
                ]),
                (new ValidationOptions()),
                [new ConstraintViolation(
                    'Message',
                    null,
                    [],
                    null,
                    'pathToConvert',
                    'value'
                )],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('pathToConvert')->setMessage('Message'),
                ]),
                (new ValidationOptions())->setViolationPathMap(['originalPath' => 'pathToConvert']),
                [new ConstraintViolation(
                    'Message',
                    null,
                    [],
                    null,
                    'originalPath',
                    'value'
                )],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('pathToConvert')->setMessage('Message1'),
                    (new Violation())->setField('convertedPath')->setMessage('Message2'),
                ]),
                (new ValidationOptions())->setViolationPathMap(['originalPath' => 'pathToConvert']),
                [
                    new ConstraintViolation(
                        'Message1',
                        null,
                        [],
                        null,
                        'originalPath',
                        'value'
                    ),
                    new ConstraintViolation(
                        'Message2',
                        null,
                        [],
                        null,
                        'pathToConvert',
                        'value'
                    ),
                ],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('path')->setMessage('Message1')->setCode('invalid_type'),
                ]),
                (new ValidationOptions()),
                [
                    new ConstraintViolation(
                        'Message1',
                        null,
                        [],
                        null,
                        'path',
                        'value',
                        null,
                        Type::INVALID_TYPE_ERROR,
                        new Type('type')
                    ),
                ],
                ['Default']
            ],
            [
                (new ApiException(ApiException::INVALID_PARAMETERS))->setViolations([
                    (new Violation())->setField('path')->setMessage('Message1')->setCode('custom code'),
                ]),
                (new ValidationOptions()),
                [
                    new ConstraintViolation(
                        'Message1',
                        null,
                        [],
                        null,
                        'path',
                        'value',
                        null,
                        'custom code',
                        new Type('type')
                    ),
                ],
                ['Default']
            ],
        ];
    }
}
