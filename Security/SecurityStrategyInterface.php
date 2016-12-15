<?php

namespace Paysera\Bundle\RestBundle\Security;

use Symfony\Component\HttpFoundation\Request;

interface SecurityStrategyInterface
{
    /**
     * @param Request $request
     *
     * @return bool
     */
    public function isAllowed(Request $request);
}
