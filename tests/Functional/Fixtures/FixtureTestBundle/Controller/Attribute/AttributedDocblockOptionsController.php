<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller\Attribute;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AttributedDocblockOptionsController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_USER", "ROLE_ADMIN"})
     */
    #[Route(path: '/attributed/docblock/testRequiredPermissions', methods: Request::METHOD_GET)]
    public function testRequiredPermissions(): Response
    {
        return new Response('OK');
    }
}
