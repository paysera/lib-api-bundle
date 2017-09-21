<?php

namespace Paysera\Bundle\RestBundle\Entity;

use Paysera\Component\Serializer\Entity\Violation;

class Error
{
    /**
     * @var string|null
     */
    private $code;

    /**
     * @var integer|null
     */
    private $statusCode;

    /**
     * @var string|null
     */
    private $uri;

    /**
     * @var string|null
     */
    private $message;

    /**
     * @var array|null
     */
    private $properties;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @var array
     */
    private $violations;

    /**
     * Creates self. For fluent interface
     *
     * @return self
     */
    static public function create()
    {
        return new self();
    }

    /**
     * @param string $code
     * @param integer $statusCode
     * @param string $message
     * @param string $uri
     */
    public function __construct($code = null, $statusCode = null, $message = null, $uri = null)
    {
        $this->setCode($code)->setStatusCode($statusCode)->setMessage($message)->setUri($uri);
        $this->violations = [];
    }

    /**
     * @param string|null $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param integer|null $statusCode
     *
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return integer|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param string|null $uri
     *
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string|null $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
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
    public function setViolations(array $violations)
    {
        $this->violations = $violations;
        return $this;
    }
}
