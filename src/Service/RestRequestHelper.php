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
     * Resolves REST related options for current route.
     * If request is configured for via annotations for REST support, returns RestRequestOptions.
     *
     * @param Request $request
     *
     * @return RestRequestOptions|null
     *
     * @internal
     */
    public function resolveRestRequestOptionsForRequest(Request $request)
    {
        $serialized = $request->attributes->get(self::SERIALIZED_REST_OPTIONS_KEY);
        if ($serialized !== null) {
            return unserialize($serialized);
        }

        return null;
    }

    /**
     * Resolves REST related options for current request.
     * If request is configured via custom configuration for REST support, returns RestRequestOptions.
     *
     * @param Request $request
     * @param callable $controller
     *
     * @return RestRequestOptions|null
     *
     * @internal
     */
    public function resolveRestRequestOptionsForController(Request $request, callable $controller)
    {
        $controllerIdentifier = $request->attributes->get('_controller');
        $options = $controllerIdentifier !== null
            ? $this->restRequestOptionsRegistry->getRestRequestOptionsForController($controllerIdentifier)
            : null
        ;
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
     * Only works after setting Options – use either
     * resolveRestRequestOptionsForController or resolveRestRequestOptionsForRequest before that
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
