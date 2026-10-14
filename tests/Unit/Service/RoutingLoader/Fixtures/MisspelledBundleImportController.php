<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermission;
use Symfony\Component\Routing\Attribute\Route;

class MisspelledBundleImportController
{
    /**
     * @RequiredPermission(permissions={"ROLE_ADMIN"})
     */
    #[Route('/misspelled-bundle-import', methods: ['GET'])]
    public function show()
    {
    }
}
