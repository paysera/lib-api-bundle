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

    public static function provider(): array
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
            'higher priority wins, from Symfony 7.1 through the language of the first tag' => [
                $tagsFormattedBySymfony ? 'en' : 'de',
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
                'de-DE',
                true,
            ],
            'a tag that the former placeholder default starts with' => [
                $tagsFormattedBySymfony ? 'de' : 'unchanged',
                ['de'],
                'd',
                true,
            ],
            'a bare language, from Symfony 7.1 matched to a configured locale with a region' => [
                $tagsFormattedBySymfony ? 'en_US' : 'unchanged',
                ['en_US', 'lt_LT'],
                'en',
                true,
            ],
            'another region of the language, from Symfony 7.1 matched to a configured locale with a region' => [
                $tagsFormattedBySymfony ? 'en_US' : 'unchanged',
                ['en_US', 'lt_LT'],
                'en-GB',
                true,
            ],
            'a bare language of the second configured locale' => [
                $tagsFormattedBySymfony ? 'lt_LT' : 'unchanged',
                ['en_US', 'lt_LT'],
                'lt',
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
            'a locale configured as a number' => [
                '1',
                [1],
                'de, 1',
                true,
            ],
            'a malformed item is not a language' => [
                'unchanged',
                ['de'],
                "'",
                true,
            ],
            'an empty i- item, which Symfony 3.4 to 7.0 reads as an empty language, matching the empty placeholder' => [
                $tagsFormattedBySymfony ? 'de' : 'unchanged',
                ['de'],
                'i-, de',
                true,
            ],
            'the language default, which matched the former placeholder' => [
                'de',
                ['de'],
                'default, de',
                true,
            ],
            'the language i-default, which matched the former placeholder' => [
                'de',
                ['de'],
                'i-default, de',
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
