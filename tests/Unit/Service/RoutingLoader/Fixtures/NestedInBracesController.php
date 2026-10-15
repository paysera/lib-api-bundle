<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class NestedInBracesController
{
    /**
     * @RequiredPermissions(permissions={@Rest\Validation})
     */
    #[Route('/nested-in-braces', methods: ['GET'])]
    public function show()
    {
    }
}
