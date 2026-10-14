<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class FullyQualifiedDoubledBackslashController
{
    /**
     * @Rest\RequiredPermissions(permissions={"ROLE_USER"})
     * @\Paysera\Bundle\ApiBundle\Annotation\\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/fully-qualified-doubled-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
