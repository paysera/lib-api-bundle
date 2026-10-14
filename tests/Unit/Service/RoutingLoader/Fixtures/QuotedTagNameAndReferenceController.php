<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class QuotedTagNameAndReferenceController
{
    /**
     * Replaces the "@Support\Legacy" tag.
     *
     * @RequiredPermissions(permissions={Support\Roles::ADMIN})
     */
    #[Route('/quoted-tag-name-and-reference', methods: ['GET'])]
    public function show()
    {
    }
}
