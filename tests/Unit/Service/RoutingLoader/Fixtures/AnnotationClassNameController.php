<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Support\AuditAccess;
use Symfony\Component\Routing\Attribute\Route;

class AnnotationClassNameController
{
    /**
     * @ResponseNormalization(normalizationType=AuditAccess::class)
     */
    #[Route('/annotation-class-name', methods: ['GET'])]
    public function show()
    {
    }
}
