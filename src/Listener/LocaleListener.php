<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Listener;

use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Symfony\Component\HttpFoundation\AcceptHeader;
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

    private function resolveFromHeaders(Request $request): ?string
    {
        $languages = $this->readLanguages($request);
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

    /**
     * @return string[]
     */
    private function readLanguages(Request $request): array
    {
        $languages = [];
        foreach (AcceptHeader::fromString($request->headers->get('Accept-Language'))->all() as $item) {
            $language = (string)$item->getValue();
            if (strpos($language, '-') !== false) {
                $codes = explode('-', $language);
                if ($codes[0] === 'i') {
                    $language = $codes[1];
                } else {
                    $language = strtolower($codes[0]);
                    for ($i = 1, $count = count($codes); $i < $count; $i++) {
                        $language .= '_' . strtoupper($codes[$i]);
                    }
                }
            }
            $languages[] = $language;
        }

        return $languages;
    }
}
