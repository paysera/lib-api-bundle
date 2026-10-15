<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class ColonInTagNameController
{
    /**
     * @Rest\RequiredPermissions:admin(permissions={"ROLE_ADMIN"})
     */
    #[Route('/colon-in-tag-name', methods: ['GET'])]
    public function show()
    {
    }
}
