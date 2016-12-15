<?php

namespace Paysera\Bundle\RestBundle\Normalizer;

use Paysera\Component\Serializer\Normalizer\BaseDenormalizer;
use Symfony\Component\Validator\ConstraintViolation;
use Paysera\Component\Serializer\Exception\InvalidDataException;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ValidatorAwareDenormalizer extends BaseDenormalizer
{
    /**
     * @var ValidatorInterface|LegacyValidatorInterface
     */
    protected $validator;

    /**
     * Called from configuration
     *
     * @param ValidatorInterface|LegacyValidatorInterface $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validates entity, throws InvalidDataException if some constraint fails
     *
     * @param object $entity
     *
     * @param array|null $groups
     * @throws InvalidDataException
     */
    protected function validate($entity, array $groups = null)
    {
        if ($this->validator === null) {
            throw new \RuntimeException('No validator was set to mapper');
        }
        $violationList = $this->validator->validate($entity, $groups);
        if ($violationList->count() > 0) {
            $message = null;
            $properties = array();

            /** @var ConstraintViolation $violation */
            foreach ($violationList as $violation) {
                if ($message === null) {
                    $message = $violation->getMessage();
                }

                $properties[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            $exception = new InvalidDataException($message);
            $exception->setProperties($properties);

            throw $exception;
        }
    }
}
