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

    /**
     * The first language of Accept-Language, in the client's order of preference, that is a configured locale; a
     * regional variant (de_CH) also offers its primary language (de) unless the header lists that language itself.
     *
     * This is the rule Request::getPreferredLanguage() applied up to Symfony 7.0. Symfony 7.1 changed it, and passing a
     * placeholder for "no match" stopped working there ("default" starts with "de"), so the listener matches itself and
     * picks the same locale on every Symfony line.
     */
    private function resolveFromHeaders(Request $request): ?string
    {
        $languages = $request->getLanguages();
        $candidates = [];
        foreach ($languages as $language) {
            $candidates[] = $language;
            $separatorPosition = strpos($language, '_');
            if ($separatorPosition === false) {
                continue;
            }
            $primaryLanguage = substr($language, 0, $separatorPosition);
            if (!in_array($primaryLanguage, $languages, true)) {
                $candidates[] = $primaryLanguage;
            }
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $this->locales, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
