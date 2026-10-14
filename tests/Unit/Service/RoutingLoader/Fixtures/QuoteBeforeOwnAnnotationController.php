<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class QuoteBeforeOwnAnnotationController
{
    /**
     * Exports the fields wrapped in " characters.
     *
     * @AuditAccess
     *
     * Logged as "audit".
     */
    #[Route('/quote-before-own-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
