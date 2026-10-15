<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class_alias(AuditAccess::class, __NAMESPACE__ . '\\Legacy\\AuditAccess');

class ClassAliasedOwnAnnotationController
{
    /**
     * @Legacy\AuditAccess
     */
    #[Route('/class-aliased-own-annotation', methods: ['GET'])]
    public function show()
    {
    }
}
