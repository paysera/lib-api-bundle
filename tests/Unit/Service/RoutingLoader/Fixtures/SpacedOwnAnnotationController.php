<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support as Own;
use Symfony\Component\Routing\Attribute\Route;

class SpacedOwnAnnotationController
{
    /**
     * @Own \AuditAccess
     */
    #[Route('/spaced-own-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
