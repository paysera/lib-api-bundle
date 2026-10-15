<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\support as Own;
use Symfony\Component\Routing\Attribute\Route;

class MiscasedOwnAliasImportController
{
    /**
     * @Own\AuditAccess
     */
    #[Route('/miscased-own-alias-import', methods: ['GET'])]
    public function show()
    {
    }
}
