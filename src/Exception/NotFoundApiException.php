<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Exception;

class NotFoundApiException extends ApiException
{
    public function __construct($message = null, $statusCode = null, $previous = null)
    {
        parent::__construct(self::NOT_FOUND, $message, $statusCode, $previous);
    }
}
