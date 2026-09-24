<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Listener;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Listener\LocaleListener;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Tests\Unit\Helper\HttpKernelHelper;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TypeError;

class LocaleListenerTest extends MockeryTestCase
{
    /**
     * @dataProvider provider
     *
     * @param string $expectedLocale
     * @param array $locales
     * @param string $acceptLanguage
     * @param bool $rest
     */
    public function testOnKernelRequest(string $expectedLocale, array $locales, string $acceptLanguage, bool $rest)
    {
        $this->assertSame($expectedLocale, $this->resolveLocale($locales, $acceptLanguage, $rest));
    }

    public function testAnItemOfOnlyASemicolonIsNotALanguage()
    {
        try {
            AcceptHeader::fromString(';');
        } catch (TypeError $error) {
            $this->markTestSkipped('This http-foundation release fails on an empty Accept-Language item itself');
        }

        $this->assertSame('unchanged', $this->resolveLocale(['de'], ';', true));
    }

    /**
     * @param string[] $locales
     */
    private function resolveLocale(array $locales, string $acceptLanguage, bool $rest): string
    {
        $helper = Mockery::mock(RestRequestHelper::class);
        $kernel = Mockery::mock(HttpKernelInterface::class);
        $listener = new LocaleListener($helper, $locales);
        $request = new Request();
        $request->setLocale('unchanged');
        $request->headers->set('Accept-Language', $acceptLanguage);

        $helper->shouldReceive('isRestRequest')->with($request)->andReturn($rest);

        if (class_exists('Symfony\Component\HttpKernel\Event\RequestEvent')) {
            $event = new RequestEvent($kernel, $request, HttpKernelHelper::getMainRequestConstValue());
        } else {
            $event = new GetResponseEvent($kernel, $request, HttpKernelHelper::getMainRequestConstValue());
        }

        $listener->onKernelRequest($event);

        return $request->getLocale();
    }

    public function provider()
    {
        return [
            [
                'unchanged',
                ['en'],
                'en',
                false,
            ],
            [
                'en',
                ['en'],
                'en',
                true,
            ],
            [
                'unchanged',
                ['de'],
                'en',
                true,
            ],
            [
                'de',
                ['de'],
                'en, lt;q=0.8, de;q=0.9',
                true,
            ],
            [
                'de',
                ['de'],
                'de-CH',
                true,
            ],
            [
                'de',
                ['de'],
                'en, de-CH',
                true,
            ],
            [
                'en',
                ['de', 'en'],
                'en, de-CH',
                true,
            ],
            'higher priority wins' => [
                'de',
                ['de', 'en'],
                'en-US,en;q=0.8, de-CH;q=0.9',
                true,
            ],
            'German among other locales' => [
                'de',
                ['en', 'lt', 'de'],
                'de',
                true,
            ],
            'German region among other locales' => [
                'de',
                ['en', 'lt', 'de'],
                'de-DE, en;q=0.5',
                true,
            ],
            'primary language is not added when the header lists it' => [
                'de',
                ['de', 'en'],
                'en-US, en;q=0.5, de;q=0.9',
                true,
            ],
            'no match keeps the locale' => [
                'unchanged',
                ['en', 'lt', 'de'],
                'fr-FR, fr;q=0.9',
                true,
            ],
            'no header keeps the locale' => [
                'unchanged',
                ['en', 'lt', 'de'],
                '',
                true,
            ],
            'a tag without a region keeps its case, as Symfony 3.4 to 7.0 read it' => [
                'unchanged',
                ['de'],
                'DE',
                true,
            ],
            'a wrong-case tag does not match before a matching one' => [
                'de',
                ['en', 'lt', 'de'],
                'EN,de',
                true,
            ],
            'a numeric tag is not a language' => [
                'unchanged',
                ['de'],
                '1',
                true,
            ],
            'a numeric tag next to a language' => [
                'de',
                ['de'],
                'de, 1',
                true,
            ],
            'a language registered with the i- prefix' => [
                'cherokee',
                ['en', 'cherokee'],
                'i-cherokee',
                true,
            ],
            'a malformed item is not a language' => [
                'unchanged',
                ['de'],
                "'",
                true,
            ],
            'a malformed item next to a language' => [
                'de',
                ['de'],
                "de, '",
                true,
            ],
        ];
    }
}
