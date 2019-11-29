<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service;

use Paysera\Bundle\ApiBundle\Entity\RestRequestOptions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class RestRequestHelper
{
    const REST_OPTIONS_KEY = 'paysera_api_options';
    const SERIALIZED_REST_OPTIONS_KEY = 'paysera_api_options.serialized';

    /**
     * @var RestRequestOptionsRegistry
     */
    private $restRequestOptionsRegistry;

    /**
     * @param RestRequestOptionsRegistry $restRequestOptionsRegistry
     *
     * @internal
     */
    public function __construct(RestRequestOptionsRegistry $restRequestOptionsRegistry)
    {
        $this->restRequestOptionsRegistry = $restRequestOptionsRegistry;
    }

    /**
     * Resolves REST related options for current request.
     * If request is configured for REST support, returns null.
     *
     * @param Request $request
     * @param callable $controller
     * @return null|RestRequestOptions
     *
     * @internal
     */
    public function resolveRestRequestOptions(Request $request, callable $controller)
    {
        $serialized = $request->attributes->get(self::SERIALIZED_REST_OPTIONS_KEY);
        if ($serialized !== null) {
            return unserialize($serialized);
        }

        $controllerIdentifier = $request->attributes->get('_controller');
        $options = $controllerIdentifier !== null
            ? $this->restRequestOptionsRegistry->getRestRequestOptionsForController($controllerIdentifier)
            : null;
        if ($options !== null) {
            return $options;
        }

        if (is_array($controller) && count($controller) === 2) {
            $controllerAsArray = $controller;
            if (is_object($controller[0])) {
                $controllerAsArray[0] = get_class($controller[0]);
            }
            $options = $this->restRequestOptionsRegistry->getRestRequestOptionsForController(
                implode('::', $controllerAsArray)
            );
            return $options;
        }

        return null;
    }

    /**
     * Saves options inside Request object to reuse later
     *
     * @param Request $request
     * @param RestRequestOptions $options
     */
    public function setOptionsForRequest(Request $request, RestRequestOptions $options)
    {
        $request->attributes->set(self::REST_OPTIONS_KEY, $options);
    }

    /**
     * Gets options saved inside Request object
     *
     * @param Request $request
     * @return RestRequestOptions
     */
    public function getOptionsFromRequest(Request $request): RestRequestOptions
    {
        return $request->attributes->get(self::REST_OPTIONS_KEY);
    }

    /**
     * Returns whether this is REST configured request.
     * Only works after setting Options – use resolveRestRequestOptions before that
     *
     * @param Request $request
     * @return bool
     */
    public function isRestRequest(Request $request): bool
    {
        return $request->attributes->has(self::REST_OPTIONS_KEY);
    }

    /**
     * Binds REST request options to this route to be able to resolve the same options when the route is matched
     *
     * @param Route $route
     * @param RestRequestOptions $restRequestOptions
     */
    public function setOptionsForRoute(Route $route, RestRequestOptions $restRequestOptions)
    {
        $route->setDefault(self::SERIALIZED_REST_OPTIONS_KEY, serialize($restRequestOptions));
    }
}
