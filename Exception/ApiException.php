<?php

namespace Paysera\Bundle\RestBundle\Exception;

use Paysera\Component\Serializer\Entity\Violation;
use Exception;

class ApiException extends Exception
{
    const INVALID_REQUEST = 'invalid_request';
    const INVALID_PARAMETERS = 'invalid_parameters';
    const INVALID_STATE = 'invalid_state';
    const INVALID_GRANT = 'invalid_grant';
    const INVALID_CODE = 'invalid_code';
    const UNAUTHORIZED = 'unauthorized';
    const FORBIDDEN = 'forbidden';
    const NOT_FOUND = 'not_found';
    const RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    const INTERNAL_SERVER_ERROR = 'internal_server_error';
    const NOT_ACCEPTABLE = 'not_acceptable';

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var integer
     */
    private $statusCode;

    /**
     * @var array|null
     */
    private $properties;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var Violation[]
     */
    private $violations;

    /**
     * @param string $errorCode
     * @param string|null $message
     * @param integer $statusCode
     * @param Exception|null $previous
     * @param array|null $properties
     * @param array|null $data
     * @param array $violations
     */
    public function __construct(
        $errorCode,
        $message = null,
        $statusCode = 0,
        Exception $previous = null,
        $properties = null,
        $data = null,
        array $violations = []
    ) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->properties = $properties;
        $this->data = $data;
        $this->violations = $violations;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param array|null $properties
     *
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array|null $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Violation[]
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param Violation[] $violations
     *
     * @return $this
     */
    public function setViolations($violations)
    {
        $this->violations = $violations;
        return $this;
    }
}
