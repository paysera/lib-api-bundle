<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class FullyQualifiedMissingClassController
{
    /**
     * @\Missing\Library\Cache(maxAge=60)
     */
    #[Route('/fully-qualified-missing-class', methods: ['GET'])]
    public function show()
    {
    }
}
