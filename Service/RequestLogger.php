<?php

namespace Paysera\Bundle\RestBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class RequestLogger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param Request $request
     * @param array $parts
     */
    public function log(Request $request, $parts)
    {
        if ($parts['url']) {
            $this->logger->debug('Request url', array(
                'Url' => $request->getRequestUri(),
            ));
        }
        if ($parts['header']) {
            $this->logger->debug('Request header', array(
                'Authorization' => $request->headers->get('AUTHORIZATION'),
                'Content-Type' => $request->headers->get('CONTENT_TYPE'),
            ));
        }
        if ($parts['content'] && $content = $request->getContent()) {
            $this->logger->debug('Request content', array($content));
        }
    }
}
