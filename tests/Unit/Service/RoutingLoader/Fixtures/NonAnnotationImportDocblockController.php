<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\Validation;
use Symfony\Component\Routing\Attribute\Route;

class NonAnnotationImportDocblockController
{
    /**
     * @Validation(enabled=false)
     */
    #[Route('/non-annotation-import', methods: ['GET'])]
    public function show()
    {
    }
}
