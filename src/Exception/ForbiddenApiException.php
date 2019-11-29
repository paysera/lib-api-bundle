<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Exception;

class ForbiddenApiException extends ApiException
{
    public function __construct($message = null, $statusCode = null, $previous = null)
    {
        parent::__construct(self::FORBIDDEN, $message, $statusCode, $previous);
    }
}
