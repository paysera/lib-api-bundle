<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class SeeTagBeforeAnnotationClassController
{
    /**
     * @see \Paysera\Bundle\ApiBundle\Annotation\Query
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    #[Route('/see-tag-before-annotation-class', methods: ['GET'])]
    public function show()
    {
    }
}
