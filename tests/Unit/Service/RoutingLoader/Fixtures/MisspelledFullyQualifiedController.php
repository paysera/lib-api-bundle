<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class MisspelledFullyQualifiedController
{
    /**
     * @\Paysera\Bundle\ApiBundle\Annotation\RequiredPermission(permissions={"ROLE_ADMIN"})
     */
    #[Route('/misspelled-fully-qualified', methods: ['GET'])]
    public function show()
    {
    }
}
