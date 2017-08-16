<?php

namespace Paysera\Bundle\RestBundle\Exception;

class ApiException extends \Exception
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
    protected $errorCode;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @var array|null
     */
    protected $properties;

    /**
     * @var array|null
     */
    protected $data;

    /**
     * @var array|null
     */
    protected $codes;

    /**
     * @param string $errorCode
     * @param string $message
     * @param integer $statusCode
     * @param \Exception $previous
     * @param array $properties
     * @param array $data
     * @param array $codes
     */
    public function __construct($errorCode, $message = null, $statusCode = 0, $previous = null, $properties = null, $data = null, $codes = null) {
        parent::__construct($message, 0, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->properties = $properties;
        $this->data = $data;
        $this->codes = $codes;
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
     * @return array|null
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * @param array|null $codes
     *
     * @return $this
     */
    public function setCodes($codes)
    {
        $this->codes = $codes;
        return $this;
    }
}
