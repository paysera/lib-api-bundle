<?php

namespace Paysera\Bundle\RestBundle\Security;

use Symfony\Component\HttpFoundation\Request;

class CompositeSecurityStrategy implements SecurityStrategyInterface
{
    /**
     * @var SecurityStrategyInterface[]
     */
    protected $strategies = array();


    public function __construct($strategies)
    {
        $this->strategies = $strategies;
    }

    public function isAllowed(Request $request)
    {
        foreach ($this->strategies as $strategy) {
            if (!$strategy->isAllowed($request)) {
                return false;
            }
        }

        return true;
    }
} 
