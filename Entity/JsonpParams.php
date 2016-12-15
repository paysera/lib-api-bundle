<?php

namespace Paysera\Bundle\RestBundle\Entity;

class JsonpParams
{
    /**
     * @var string
     */
    protected $parameter;

    /**
     * @var string
     */
    protected $callback;

    /**
     * Sets callback
     *
     * @param string $callback
     *
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Gets callback
     *
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Sets parameter
     *
     * @param string $parameter
     *
     * @return $this
     */
    public function setParameter($parameter)
    {
        $this->parameter = $parameter;

        return $this;
    }

    /**
     * Gets parameter
     *
     * @return string
     */
    public function getParameter()
    {
        return $this->parameter;
    }
} 
