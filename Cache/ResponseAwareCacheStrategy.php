<?php

namespace Paysera\Bundle\RestBundle\Cache;

use Symfony\Component\HttpFoundation\Response;

interface ResponseAwareCacheStrategy
{
    public function setResponse(Response $response);
}
