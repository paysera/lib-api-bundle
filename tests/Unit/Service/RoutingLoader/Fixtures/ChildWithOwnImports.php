<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

/**
 * @RequiredPermissions(permissions={"ROLE_CLASS"})
 */
class ChildWithOwnImports extends ParentWithDocblockOptions
{
}
