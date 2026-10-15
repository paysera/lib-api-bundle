<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation as Rest;
use Symfony\Component\Routing\Attribute\Route;

class NestedDoubledBackslashController
{
    /**
     * @Rest\RequiredPermissions(@Rest\\Validation)
     */
    #[Route('/nested-doubled-backslash', methods: ['GET'])]
    public function show()
    {
    }
}
