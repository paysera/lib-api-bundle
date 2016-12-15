<?php

namespace Paysera\Bundle\RestBundle\Exception;

class ForbiddenApiException extends ApiException
{
    public function __construct($message = null, $statusCode = null, $previous = null)
    {
        parent::__construct(self::FORBIDDEN, $message, $statusCode, $previous);
    }
}
