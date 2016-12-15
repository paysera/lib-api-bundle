<?php

namespace Paysera\Bundle\RestBundle\Cache;

use Symfony\Component\HttpFoundation\Response;

class PublicCacheStrategy extends DefaultCacheStrategy implements ResponseAwareCacheStrategy
{
    public function setResponse(Response $response)
    {
        $response->setPublic();
    }
}
