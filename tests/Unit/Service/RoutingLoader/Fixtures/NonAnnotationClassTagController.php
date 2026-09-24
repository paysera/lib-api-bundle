<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Required;

class NonAnnotationClassTagController
{
    /**
     * @Attribute ( @RequiredPermissions(permissions={"ROLE_ADMIN"}) )
     */
    public function show()
    {
    }

    /**
     * @Required ( @RequiredPermissions(permissions={"ROLE_ADMIN"}) )
     */
    public function afterAnImportedNonAnnotationClass()
    {
    }
}
