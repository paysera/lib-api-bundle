<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\Validation;

class NotTopLevelAnnotationsController
{
    /**
     * @Query(parameterName="filter", validation=@Validation(groups={"filter"}))
     *
     * Mail ops@RequiredPermissions.example; {@RequiredPermissions} is an inline tag, not an annotation.
     */
    public function find()
    {
    }

    /**
     * @Query(parameterName="filter", validation= @Validation(groups={"filter"}))
     */
    public function findWithASpaceBeforeTheNestedAnnotation()
    {
    }

    /**
@RequiredPermissions(permissions={"ROLE_ADMIN"})
     * Doctrine starts reading at the first "at" sign after a space, a tab or a star, so the line above is not read.
     */
    public function annotationAtTheStartOfALine()
    {
    }

    /**
     * @param string $size see "the note on @RequiredPermissions in the parent"
     */
    public function annotationInAString($size)
    {
    }

    /**
     * @RequiredPermissions-deprecated use the new permission model
     */
    public function annotationFollowedByADash()
    {
    }
}
