<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

class IgnoredTagNameController
{
    /**
     * @Target( @RequiredPermissions(permissions={"ROLE_ADMIN"}) )
     */
    public function show()
    {
    }
}
