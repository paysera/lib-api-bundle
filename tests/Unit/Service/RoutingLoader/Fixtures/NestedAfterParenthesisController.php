<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class NestedAfterParenthesisController
{
    /**
     * @RequiredPermissions(@Rest\Validation)
     */
    #[Route('/nested-after-parenthesis', methods: ['GET'])]
    public function show()
    {
    }
}
