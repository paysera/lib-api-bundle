<?php

declare(strict_types=1);

/*
 * Two classes in one file, each in its own namespace block with its own meaning of the alias Shared: the parent's
 * method reads its block's imports and namespace, the child's class docblock reads the child's.
 */

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherNamespace {
    use Paysera\Bundle\ApiBundle\Annotation\Body as Shared;

    abstract class ParentInAnotherNamespace
    {
        /**
         * @Shared(parameterName="item")
         * @LocalRestAnnotation
         */
        public function create()
        {
        }
    }
}

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures {
    use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions as Shared;

    /**
     * @Shared(permissions={"ROLE_CLASS"})
     * @CustomRestAnnotation
     */
    class ChildOfAParentInAnotherNamespace extends OtherNamespace\ParentInAnotherNamespace
    {
    }
}
