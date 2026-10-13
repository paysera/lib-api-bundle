<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Anotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class MisspelledImportDocblockController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/misspelled-import', methods: ['GET'])]
    public function show()
    {
    }
}
