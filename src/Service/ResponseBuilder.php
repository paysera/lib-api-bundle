<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use RuntimeException;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class ResponseBuilder
{
    /**
     * Builds HTTP Response object by provided data
     *
     * @param mixed $data
     * @param int $statusCode
     * @return Response
     * @throws Exception
     */
    public function buildResponse($data, int $statusCode = Response::HTTP_OK): Response
    {
        if (!is_object($data) && !is_array($data)) {
            throw new RuntimeException('Provided data for JSON response must be an object or an array');
        }

        return $this->buildJsonResponse($data, $statusCode, $this->getDefaultHeaders());
    }

    public function buildEmptyResponse(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT, $this->getDefaultHeaders());
    }

    private function getDefaultHeaders()
    {
        return [
            'X-Frame-Options' => 'DENY',
            'Cache-Control' => 'must-revalidate, no-cache, no-store, private',
        ];
    }

    private function buildJsonResponse($data, int $statusCode, array $headers)
    {
        try {
            $content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $exception) {
            if (
                get_class($exception) === 'Exception'
                && strpos($exception->getMessage(), 'Failed calling ') === 0
            ) {
                throw $exception->getPrevious() ?: $exception;
            }
            throw $exception;
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return new Response($content, $statusCode, ['Content-Type' => 'application/json'] + $headers);
    }
}
