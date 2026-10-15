<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class ColonInImportedOwnAnnotationController
{
    /**
     * @AuditAccess:strict
     */
    #[Route('/colon-in-imported-own-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
