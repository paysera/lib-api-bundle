<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class MiscasedImportNextToRejectedTagController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     * @\Missing\Library\Cache
     */
    #[Route('/miscased-import-next-to-rejected-tag', methods: ['GET'])]
    public function show()
    {
    }
}
