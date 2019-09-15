<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;

/**
 * @internal
 */
class RestRequestOptionsRegistry
{
    /**
     * @var array|RestRequestOptions[] associative array
     */
    private $restRequestOptionsByController;

    public function __construct()
    {
        $this->restRequestOptionsByController = [];
    }

    public function registerRestRequestOptions(RestRequestOptions $options, string $controller)
    {
        $this->restRequestOptionsByController[$controller] = $options;
    }

    /**
     * @param string $controller
     * @return RestRequestOptions|null
     */
    public function getRestRequestOptionsForController(string $controller)
    {
        return $this->restRequestOptionsByController[$controller] ?? null;
    }
}
