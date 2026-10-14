<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationRelativeController
{
    /**
     * @Support\AuditAccess
     */
    #[Route('/own-annotation-relative', methods: ['GET'])]
    public function show()
    {
    }
}
