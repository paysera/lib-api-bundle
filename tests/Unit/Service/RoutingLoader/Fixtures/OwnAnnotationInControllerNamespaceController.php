<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Symfony\Component\Routing\Attribute\Route;

class OwnAnnotationInControllerNamespaceController
{
    /**
     * @Audited
     */
    #[Route('/own-annotation-in-controller-namespace', methods: ['GET'])]
    public function show()
    {
    }
}
