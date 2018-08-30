<?php

namespace Paysera\Bundle\RestBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class ExceptionLogger
{
    public function log(LoggerInterface $logger, Response $response, Exception $exception)
    {
        if ($response->getStatusCode() === 500) {
            $logger->error($exception->getMessage(), ['exception' => $exception]);
        } elseif ($response->getStatusCode() === 404) {
            $logger->notice($exception->getMessage(), ['exception' => $exception]);
        } else {
            $logger->warning($exception->getMessage(), ['exception' => $exception]);
        }
    }
}
