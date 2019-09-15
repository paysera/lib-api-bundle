<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\Validation;

use Paysera\Bundle\RestBundle\Entity\ValidationOptions;
use Paysera\Bundle\RestBundle\Entity\Violation;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolation;
use RuntimeException;

/**
 * Purpose:
 *     1. detect invalid properties
 *     2. throws exception with detailed information about each invalid property (name, message, violations).
 *
 * Use case:
 *     1. front-end sends data to REST API. If data invalid, then front-end will get detailed information
 *        about each invalid property (name, message).
 */
class EntityValidator
{
    protected $validator;
    protected $propertyPathConverter;

    public function __construct(
        ValidatorInterface $validator = null,
        PropertyPathConverterInterface $propertyPathConverter = null
    ) {
        $this->validator = $validator;
        $this->propertyPathConverter = $propertyPathConverter;
    }

    /**
     * Validates entity, throws InvalidDataException if some constraint fails.
     *
     * @param mixed $entity
     * @param ValidationOptions $options
     *
     * @throws ApiException
     */
    public function validate($entity, ValidationOptions $options)
    {
        if (!is_object($entity)) {
            return;
        }

        if ($this->validator === null) {
            throw new RuntimeException(
                'To use validation in RestBundle you must configure framework.validation in config.yml'
            );
        }

        $violationList = $this->validator->validate($entity, null, $options->getValidationGroups());

        if ($violationList->count() === 0) {
            return;
        }

        $violations = [];

        $violationPathMap = $options->getViolationPathMap();
        foreach ($violationList as $violation) {
            $path = $violation->getPropertyPath();
            if (isset($violationPathMap[$path])) {
                $path = $violationPathMap[$path];
            } elseif ($this->propertyPathConverter !== null) {
                $path = $this->propertyPathConverter->convert($path);
            }

            $violations[] = (new Violation())
                ->setField($path)
                ->setMessage($violation->getMessage())
                ->setCode($this->getErrorCode($violation))
            ;
        }

        $exception = new ApiException(ApiException::INVALID_PARAMETERS);
        $exception->setViolations($violations);
        throw $exception;
    }

    /**
     * @param ConstraintViolation $violation
     *
     * @return null|string
     */
    private function getErrorCode(ConstraintViolation $violation)
    {
        $constraint = $violation->getConstraint();

        if ($constraint === null || $violation->getCode() === null) {
            return null;
        }

        try {
            return mb_strtolower(
                str_replace('_ERROR', '', $constraint->getErrorName($violation->getCode()))
            );
        } catch (InvalidArgumentException $exception) {
            return $violation->getCode();
        }
    }
}
