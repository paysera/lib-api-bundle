<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;

class NoBreakSpaceController
{
    /**
     * The annotation below follows a no-break space (U+00A0), which Doctrine's lexer reads as whitespace.
     *
     * @param int $id
     * @RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function show($id)
    {
    }
}
