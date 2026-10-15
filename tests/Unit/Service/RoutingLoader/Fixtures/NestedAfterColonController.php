<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class NestedAfterColonController
{
    /**
     * @RequiredPermissions(permissions={"admin":@Rest\Validation})
     */
    #[Route('/nested-after-colon', methods: ['GET'])]
    public function show()
    {
    }
}
