<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Controller;

use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RequiredPermissions(permissions: ['ROLE_USER'])]
class AttributedClassRequiredPermissionsController
{
    /**
     * @Route(path="/attributed/class/testRequiredPermissions", methods={"GET"})
     */
    #[RequiredPermissions(permissions: ['ROLE_USER'])]
    #[RequiredPermissions(permissions: ['ROLE_ADMIN'])]
    #[RequiredPermissions(permissions: ['ROLE_USER'])]
    public function test(): Response
    {
        return new Response('OK');
    }

    /**
     * @Route(path="/attributed/class/simpleAction", methods={"GET"})
     */
    public function simpleAction(): Response
    {
        return new Response('OK');
    }
}
