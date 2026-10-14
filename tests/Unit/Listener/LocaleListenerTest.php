<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Listener;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Paysera\Bundle\ApiBundle\Listener\LocaleListener;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use Paysera\Bundle\ApiBundle\Tests\Unit\Helper\HttpKernelHelper;
use ReflectionClass;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

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
        } catch (Throwable $error) {
            $this->markTestSkipped('This http-foundation fails on an empty Accept-Language item itself');
        }

        $this->assertSame('unchanged', $this->resolveLocale(['de'], ';', true));
    }

    /**
     * @param array<int|string> $locales
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
        $tagsFormattedBySymfony = (new ReflectionClass(Request::class))->hasMethod('formatLocale');

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
            'a tag in another letter case matches the configured locale' => [
                'de',
                ['de'],
                'DE',
                true,
            ],
            'a tag in another letter case keeps its priority' => [
                'en',
                ['en', 'lt', 'de'],
                'EN,de',
                true,
            ],
            'a tag with a script matches the configured locale in any letter case' => [
                'zh_Hant_TW',
                ['en', 'zh_Hant_TW'],
                'zh-hant-tw',
                true,
            ],
            'a tag with a region matches a locale configured in another letter case' => [
                'de_DE',
                ['de_DE'],
                'de-de',
                true,
            ],
            'the primary language of a tag in another letter case' => [
                'de',
                ['de'],
                'DE-CH',
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
            'a locale configured as a number' => [
                '1',
                [1],
                'de, 1',
                true,
            ],
            'a language registered with the i- prefix' => [
                'cherokee',
                ['en', 'cherokee'],
                'i-cherokee',
                true,
            ],
            'a tag with a numeric region, which Symfony 7.1 and later reads without the region' => [
                $tagsFormattedBySymfony ? 'unchanged' : 'es_419',
                ['es_419'],
                'es-419',
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
