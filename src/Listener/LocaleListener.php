<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Listener;

use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
class LocaleListener
{
    private $requestHelper;
    private $locales;

    public function __construct(
        RestRequestHelper $requestHelper,
        array $locales
    ) {
        $this->requestHelper = $requestHelper;
        $this->locales = $locales;
    }

    /**
     * Both events are type-casted as one is deprecated from 4.3, but another not available before this version
     * @param GetResponseEvent|RequestEvent $event
     */
    public function onKernelRequest($event)
    {
        $request = $event->getRequest();

        if (!$this->requestHelper->isRestRequest($request)) {
            return;
        }

        $locale = $this->resolveFromHeaders($request);
        if ($locale !== null) {
            $request->setLocale($locale);
        }
    }

    private function resolveFromHeaders(Request $request)
    {
        $defaultLocale = 'default';
        $preferredLanguage = $request->getPreferredLanguage(array_merge([$defaultLocale], $this->locales));

        if ($preferredLanguage !== null && $preferredLanguage !== $defaultLocale) {
            return $preferredLanguage;
        }

        return null;
    }
}
