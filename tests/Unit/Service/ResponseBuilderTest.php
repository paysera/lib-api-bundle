<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service;

use InvalidArgumentException;
use JsonSerializable;
use Paysera\Bundle\RestBundle\Service\ResponseBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ResponseBuilderTest extends TestCase
{
    public function testBuildResponse()
    {
        $builder = new ResponseBuilder();
        $response = $builder->buildResponse(['with unicode: ačiū' => '</script'], 403);

        $this->assertEquals(new Response('{"with unicode: ačiū":"</script"}', 403, [
            'Content-Type' => 'application/json',
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
        ]), $response);
    }

    public function testBuildResponseWithScalarValue()
    {
        $builder = new ResponseBuilder();
        $this->expectException(RuntimeException::class);
        $builder->buildResponse('string');
    }

    public function testBuildResponseWithInvalidUtfSequence()
    {
        $builder = new ResponseBuilder();
        $this->expectException(InvalidArgumentException::class);
        $builder->buildResponse(['text' => chr(128)]);
    }

    public function testBuildResponseWithJsonSerializable()
    {
        $builder = new ResponseBuilder();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected');
        $builder->buildResponse(new class() implements JsonSerializable {
            public function jsonSerialize()
            {
                throw new RuntimeException('expected');
            }
        });
    }

    public function testBuildEmptyResponse()
    {
        $builder = new ResponseBuilder();
        $response = $builder->buildEmptyResponse();

        $this->assertEquals(new Response('', 204, [
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
        ]), $response);
    }
}
