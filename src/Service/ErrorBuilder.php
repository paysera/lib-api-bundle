<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use Paysera\Component\ObjectWrapper\Exception\InvalidItemException;
use Paysera\Component\Normalization\Exception\InvalidDataException;
use Paysera\Pagination\Exception\InvalidCursorException;
use Paysera\Pagination\Exception\TooLargeOffsetException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Paysera\Bundle\RestBundle\Entity\Error;
use Paysera\Bundle\RestBundle\Exception\ApiException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Exception;

class ErrorBuilder implements ErrorBuilderInterface
{
    private $errorConfiguration;

    public function __construct()
    {
        $this->errorConfiguration = [];
    }

    public function configureError(string $errorCode, int $statusCode, string $message)
    {
        $this->errorConfiguration[$errorCode] = [
            'statusCode' => $statusCode,
            'message' => $message,
        ];
    }

    public function createErrorFromException(Exception $exception): Error
    {
        return $this->fillErrorFields($this->buildErrorFromException($exception));
    }

    private function buildErrorFromException(Exception $exception): Error
    {
        if ($exception instanceof ApiException) {
            return (new Error())
                ->setCode($exception->getErrorCode())
                ->setMessage($exception->getMessage())
                ->setStatusCode($exception->getStatusCode())
                ->setProperties($exception->getProperties())
                ->setData($exception->getData())
                ->setViolations($exception->getViolations())
            ;
        } elseif ($exception instanceof InvalidDataException) {
            return (new Error())
                ->setCode(ApiException::INVALID_PARAMETERS)
                ->setMessage($exception->getMessage())
            ;
        } elseif ($exception instanceof InvalidItemException) {
            return (new Error())
                ->setCode(ApiException::INVALID_PARAMETERS)
                ->setMessage($exception->getMessage())
            ;
        } elseif ($exception instanceof TooLargeOffsetException) {
            return (new Error())
                ->setCode(ApiException::OFFSET_TOO_LARGE)
                ->setMessage($exception->getMessage())
            ;
        } elseif ($exception instanceof InvalidCursorException) {
            return (new Error())
                ->setCode(ApiException::INVALID_CURSOR)
                ->setMessage($exception->getMessage() ?: 'Provided cursor is invalid')
            ;
        } elseif ($exception instanceof AuthenticationCredentialsNotFoundException) {
            return (new Error())
                ->setCode(ApiException::UNAUTHORIZED)
                ->setMessage('No authorization data found')
            ;
        } elseif ($exception instanceof AuthenticationException) {
            $error = (new Error())->setCode(ApiException::UNAUTHORIZED);
            if ($exception->getCode() === 999) {
                $error->setMessage($exception->getMessage());
            }
            return $error;
        } elseif ($exception instanceof AccessDeniedException) {
            return (new Error())
                ->setCode(ApiException::FORBIDDEN)
                ->setMessage($exception->getMessage())
            ;
        } elseif ($exception instanceof AccessDeniedHttpException) {
            return (new Error())
                ->setCode(ApiException::FORBIDDEN)
                ->setMessage($exception->getMessage())
            ;
        } elseif ($exception instanceof ResourceNotFoundException || $exception instanceof NotFoundHttpException) {
            return (new Error())
                ->setCode(ApiException::NOT_FOUND)
                ->setMessage('Provided url not found')
            ;
        } elseif ($exception instanceof MethodNotAllowedException) {
            return (new Error())
                ->setCode(ApiException::NOT_FOUND)
                ->setMessage('Provided method not allowed for this url')
                ->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED)
            ;
        } elseif ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            if ($exception->getStatusCode() === Response::HTTP_NOT_FOUND) {
                return (new Error())
                    ->setCode(ApiException::NOT_FOUND)
                    ->setStatusCode($exception->getStatusCode())
                ;
            } elseif ($exception->getStatusCode() === Response::HTTP_METHOD_NOT_ALLOWED) {
                return (new Error())
                    ->setCode(ApiException::NOT_FOUND)
                    ->setStatusCode($exception->getStatusCode())
                    ->setMessage('Provided method not allowed for this url')
                ;
            } elseif ($exception->getStatusCode() === 401) {
                return (new Error())->setCode(ApiException::UNAUTHORIZED);
            } elseif ($exception->getStatusCode() === 403) {
                return (new Error())->setCode(ApiException::FORBIDDEN);
            } elseif ($exception->getStatusCode() === 400) {
                return (new Error())->setCode(ApiException::INVALID_REQUEST);
            }
        }

        return (new Error())->setCode(ApiException::INTERNAL_SERVER_ERROR)->setStatusCode(500);
    }

    private function fillErrorFields(Error $error): Error
    {
        $configuration = $this->errorConfiguration[$error->getCode()] ?? [];

        if ($error->getStatusCode() === null) {
            $error->setStatusCode($configuration['statusCode'] ?? Response::HTTP_BAD_REQUEST);
        }

        if ($error->getMessage() === null || $error->getMessage() === '') {
            $error->setMessage($configuration['message'] ?? null);
        }

        return $error;
    }
}
