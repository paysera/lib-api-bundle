<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

class NotUtf8DocblockController
{
    /**
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     *
     * The next line has a byte that is not UTF-8 (0xFC, Latin-1): doctrine/lexer 1.0 still read this docblock.
     * Endpoint of Müller.
     */
    public function show()
    {
    }
}
