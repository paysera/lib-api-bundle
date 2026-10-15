<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use \Paysera\Bundle\ApiBundle\annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class LeadingBackslashMiscasedImportController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/leading-backslash-miscased-import', methods: ['GET'])]
    public function show()
    {
    }
}
