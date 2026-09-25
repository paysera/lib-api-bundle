<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;

class StarAndQuoteBeforeAnnotationController
{
    /**
     *@RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function firstRightAfterAStar()
    {
    }

    /**
     * @ResponseNormalization *@RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function laterAfterAStar()
    {
    }

    /** "a quote"@RequiredPermissions(permissions={"ROLE_ADMIN"}) */
    public function firstGluedToAQuote()
    {
    }

    /**
     * @param string $note see "the notes"@RequiredPermissions(permissions={"ROLE_ADMIN"})
     */
    public function afterAClosingQuote($note)
    {
    }

    /**
     * @param string $size a 7"@ResponseNormalization
     */
    public function afterALoneQuote($size)
    {
    }
}
