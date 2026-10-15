<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Own;
use Symfony\Component\Routing\Attribute\Route;

class SpacedDoubleBackslashOwnAnnotationController
{
    /**
     * @Own \\AuditAccess
     */
    #[Route('/spaced-double-backslash-own-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
