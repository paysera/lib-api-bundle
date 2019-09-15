<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service;

use Paysera\Bundle\RestBundle\Service\ContentTypeMatcher;
use PHPUnit\Framework\TestCase;

class ContentTypeMatcherTest extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param bool $expected
     * @param string $contentType
     * @param array $supportedContentTypes
     */
    public function testIsContentTypeSupported(bool $expected, string $contentType, array $supportedContentTypes)
    {
        $matcher = new ContentTypeMatcher();
        $supported = $matcher->isContentTypeSupported($contentType, $supportedContentTypes);
        $this->assertSame($expected, $supported);
    }

    public function provider()
    {
        return [
            [
                true,
                'text/plain',
                ['text/plain'],
            ],
            [
                true,
                'text/plain',
                ['application/json', 'text/plain'],
            ],
            [
                true,
                'text/plain',
                ['text/*'],
            ],
            [
                true,
                'text/plain',
                ['*'],
            ],
            [
                false,
                'text/plain',
                ['application/*'],
            ],
            [
                false,
                'application-something/*',
                ['application/*'],
            ],
            [
                false,
                'text/plain',
                ['*/*'],
            ],
            [
                false,
                'text/plain',
                ['text/custom'],
            ],
            [
                false,
                'text/plain',
                ['application/plain', 'text/something', 'text/plain*'],
            ],
        ];
    }
}
