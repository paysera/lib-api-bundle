<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class NestedAfterCommaController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"},@Rest\Validation)
     */
    #[Route('/nested-after-comma', methods: ['GET'])]
    public function show()
    {
    }
}
