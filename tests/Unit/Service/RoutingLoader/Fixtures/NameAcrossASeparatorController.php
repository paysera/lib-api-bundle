<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle as Api;
use Paysera\Bundle\ApiBundle\Annotation as REST;

class NameAcrossASeparatorController
{
    /**
     * @REST\ RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function spaceAfterTheSeparator()
    {
    }

    /**
     * @Api\
     *     Annotation\RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function lineBreakAfterTheSeparator()
    {
    }

    /**
     * @REST\*RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function starAfterTheSeparator()
    {
    }

    /**
     * @Api\ Annotation\ RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function twoSeparatorsWithSpaces()
    {
    }

    /**
     * @REST\ RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function noBreakSpaceAfterTheSeparator()
    {
    }

    /**
     * @REST\ResponseNormalization
     * Only administrators.
     */
    public function nameFollowedByTextOnTheNextLine()
    {
    }
}
