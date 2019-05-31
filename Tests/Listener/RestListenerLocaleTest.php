<?php

namespace Paysera\Bundle\RestBundle\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Mockery\MockInterface;
use Paysera\Bundle\RestBundle\ApiManager;
use Symfony\Component\HttpFoundation\Request;
use Paysera\Bundle\RestBundle\Service\RequestLogger;
use Paysera\Bundle\RestBundle\Listener\RestListener;
use Paysera\Bundle\RestBundle\Service\ExceptionLogger;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Paysera\Bundle\RestBundle\Service\ParameterToEntityMapBuilder;
use Paysera\Component\Serializer\Factory\ContextAwareNormalizerFactory;

class RestListenerLocaleTest extends TestCase
{
    /**
     * @var MockInterface|GetResponseEvent
     */
    private $responseEvent;

    public function setUp()
    {
        $this->responseEvent = Mockery::mock(GetResponseEvent::class);
    }

    /**
     * @param array $acceptedLocales
     * @param string $expected
     * @param string|null $acceptLanguage
     *
     * @dataProvider dataProviderForTestLocaleIsBeingSet
     */
    public function testCorrectLocaleIsBeingSet(array $acceptedLocales, $expected, $acceptLanguage = null)
    {
        $restListener = $this->createRestListener($acceptedLocales);

        $request = new Request();

        if ($acceptLanguage !== null) {
            $request->headers->set('Accept-Language', $acceptLanguage);
        }

        $this->responseEvent->shouldReceive('getRequest')->andReturn($request);

        $restListener->onKernelRequest($this->responseEvent);

        $this->assertEquals($expected, $request->getLocale());
    }

    /**
     * @return array
     */
    public function dataProviderForTestLocaleIsBeingSet()
    {
        return [
            'choose requested locale from the provided configuration' => [
                ['lt', 'ru'],
                'lt',
                'lt',
            ],
            'if no configuration is provided, don\'t change the locale' => [
                [],
                'en',
                'fr-CH, fr;q=0.9, lt;q=0.8, de;q=0.7',
            ],
            'choose locale from the defined locale configuration despite it having lowest weight' => [
                ['de'],
                'de',
                'fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7',
            ],
            'fallback to the default locale if neither configuration nor accept language header is provided' => [
                [],
                'en',
            ],
            'fallback to the default locale if requested locale is not configured' => [
                ['ru', 'zh'],
                'en',
                'fr-CH, fr;q=0.9, lt;q=0.8, de;q=0.7',
            ],
        ];
    }

    /**
     * @param array $locales
     *
     * @return RestListener
     */
    private function createRestListener(array $locales)
    {
        $apiManager = Mockery::mock(ApiManager::class);
        $apiManager->shouldReceive('isRestRequest')->andReturn(true);

        return new RestListener(
            $apiManager,
            Mockery::mock(ContextAwareNormalizerFactory::class),
            new NullLogger(),
            Mockery::mock(ParameterToEntityMapBuilder::class),
            new RequestLogger(new NullLogger()),
            new ExceptionLogger(),
            $locales
        );
    }
}
