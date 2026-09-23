<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Attribute\Body;
use Paysera\Bundle\ApiBundle\Attribute\RequiredPermissions;
use Symfony\Component\Routing\Attribute\Route;

class AttributeOnlyController
{
    /**
     * @Route("/documented-only")
     * @Body Symfony's and other libraries' tags, and the bundle's attribute class names, are not the bundle's annotations
     *
     * @param mixed $item
     */
    #[Route('/attribute-only', methods: ['POST'])]
    #[Body(parameterName: 'item')]
    #[RequiredPermissions(['ROLE_ADMIN'])]
    public function create($item)
    {
    }
}
