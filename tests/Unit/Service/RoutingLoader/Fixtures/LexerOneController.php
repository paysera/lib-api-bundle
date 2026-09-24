<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\Target;

class LexerOneController
{
    /**
     * A no-break space (U+00A0) stands between the name and the parenthesis: doctrine/lexer 1.0 does not read it as
     * whitespace, so the parenthesis was not the annotation's arguments there, and what it holds was applied.
     *
     * @Target ( @RequiredPermissions(permissions={"ROLE_ADMIN"}) )
     */
    public function noBreakSpaceBeforeTheArguments()
    {
    }
}
