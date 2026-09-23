<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service;

use Paysera\Bundle\ApiBundle\Entity\Violation;
use Paysera\Bundle\ApiBundle\Exception\ApiException;
use Paysera\Bundle\ApiBundle\Service\ErrorBuilder;
use Paysera\Component\Normalization\Exception\InvalidDataException;
use Paysera\Component\ObjectWrapper\Exception\InvalidItemException;
use Paysera\Pagination\Exception\InvalidCursorException;
use Paysera\Pagination\Exception\InvalidOrderByException;
use Paysera\Pagination\Exception\TooLargeOffsetException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * The error every REST endpoint answers with, for each kind of exception, built by the bundle's own
 * paysera_api.error_builder service definition (Resources/config/services.xml) with the error codes it configures.
 */
class ErrorBuilderTest extends TestCase
{
    /**
     * @dataProvider exceptionDataProvider
     */
    public function testBuildsTheErrorForEachKindOfException(
        Throwable $exception,
        string $expectedCode,
        int $expectedStatusCode,
        ?string $expectedMessage
    ) {
        $error = $this->createConfiguredErrorBuilder()->createErrorFromException($exception);

        $this->assertSame(
            [$expectedCode, $expectedStatusCode, $expectedMessage],
            [$error->getCode(), $error->getStatusCode(), $error->getMessage()]
        );
    }

    /**
     * @return array<string, array{0: Throwable, 1: string, 2: int, 3: string|null}>
     */
    public static function exceptionDataProvider(): array
    {
        $tooLargeOffset = new TooLargeOffsetException(1000, 1001);
        $invalidOrderBy = new InvalidOrderByException('bogus_field');

        return [
            'API exception with its own code and status' => [
                new ApiException('custom_code', 'Custom message', 418),
                'custom_code',
                418,
                'Custom message',
            ],
            'API exception with a configured code' => [
                new ApiException(ApiException::NOT_FOUND),
                'not_found',
                404,
                'Resource was not found',
            ],
            'API exception with an unconfigured code' => [
                new ApiException('unconfigured_code'),
                'unconfigured_code',
                400,
                null,
            ],
            'invalid data' => [new InvalidDataException('Bad data'), 'invalid_parameters', 400, 'Bad data'],
            'invalid item' => [new InvalidItemException('amount'), 'invalid_parameters', 400, 'Invalid key "amount"'],
            'offset over the maximum' => [
                $tooLargeOffset,
                'offset_too_large',
                400,
                $tooLargeOffset->getMessage(),
            ],
            'invalid cursor without a message' => [
                new InvalidCursorException(),
                'invalid_cursor',
                400,
                'Provided cursor is invalid',
            ],
            'invalid cursor with a message' => [
                new InvalidCursorException('Bad cursor'),
                'invalid_cursor',
                400,
                'Bad cursor',
            ],
            'unsupported order-by field' => [
                $invalidOrderBy,
                'invalid_parameters',
                400,
                $invalidOrderBy->getMessage(),
            ],
            'no credentials' => [
                new AuthenticationCredentialsNotFoundException(),
                'unauthorized',
                401,
                'No authorization data found',
            ],
            'authentication failure' => [
                new AuthenticationException('Internal detail'),
                'unauthorized',
                401,
                'You have not provided any credentials or they are invalid',
            ],
            'authentication failure meant for the client (code 999)' => [
                new AuthenticationException('Token expired', 999),
                'unauthorized',
                401,
                'Token expired',
            ],
            'access denied by security' => [
                new AccessDeniedException('Access Denied.'),
                'forbidden',
                403,
                'Access Denied.',
            ],
            'access denied over HTTP' => [new AccessDeniedHttpException('Denied'), 'forbidden', 403, 'Denied'],
            'no route' => [new ResourceNotFoundException(), 'not_found', 404, 'Provided url not found'],
            'not found over HTTP' => [new NotFoundHttpException(), 'not_found', 404, 'Provided url not found'],
            'method not allowed by routing' => [
                new MethodNotAllowedException(['GET']),
                'not_found',
                405,
                'Provided method not allowed for this url',
            ],
            'HTTP 404' => [new HttpException(404), 'not_found', 404, 'Resource was not found'],
            'HTTP 405' => [new HttpException(405), 'not_found', 405, 'Provided method not allowed for this url'],
            'HTTP 401' => [
                new HttpException(401),
                'unauthorized',
                401,
                'You have not provided any credentials or they are invalid',
            ],
            'HTTP 403' => [
                new HttpException(403),
                'forbidden',
                403,
                'You have no rights to access requested resource or make requested action',
            ],
            'HTTP 400' => [new HttpException(400), 'invalid_request', 400, 'Request content is invalid'],
            'other HTTP client error' => [
                new HttpException(418),
                'internal_server_error',
                500,
                'Unexpected internal system error',
            ],
            'HTTP server error' => [
                new HttpException(503),
                'internal_server_error',
                500,
                'Unexpected internal system error',
            ],
            'any other exception' => [
                new RuntimeException('Secret detail'),
                'internal_server_error',
                500,
                'Unexpected internal system error',
            ],
        ];
    }

    public function testCarriesTheApiExceptionDetails()
    {
        $violation = (new Violation())->setField('amount')->setMessage('Too large');
        $exception = (new ApiException('custom_code'))
            ->setProperties(['amount' => 'Too large'])
            ->setData(['limit' => 100])
            ->setViolations([$violation])
        ;

        $error = $this->createConfiguredErrorBuilder()->createErrorFromException($exception);

        $this->assertSame(
            [['amount' => 'Too large'], ['limit' => 100], [$violation]],
            [$error->getProperties(), $error->getData(), $error->getViolations()]
        );
    }

    private function createConfiguredErrorBuilder(): ErrorBuilder
    {
        $container = new ContainerBuilder();
        (new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../../src/Resources/config')))->load('services.xml');

        return $container->get('paysera_api.error_builder');
    }
}
