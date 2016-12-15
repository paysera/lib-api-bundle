<?php

namespace Paysera\Bundle\RestBundle\Entity;

class Error
{
    /**
     * @var string|null
     */
    protected $code;

    /**
     * @var integer|null
     */
    protected $statusCode;

    /**
     * @var string|null
     */
    protected $uri;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var array|null
     */
    protected $properties;

    /**
     * @var array|null
     */
    protected $data;

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
     * Returns array representing this error. Keys are as in OAuth error
     *
     * @return array
     */
    public function toArray()
    {
        $error = array('error' => $this->code);
        if ($this->message) {
            $error['error_description'] = $this->message;
        }
        if ($this->uri) {
            $error['error_uri'] = $this->uri;
        }
        if ($this->properties) {
            $error['error_properties'] = $this->properties;
        }
        if ($this->data) {
            $error['error_data'] = $this->data;
        }
        return $error;
    }
}
