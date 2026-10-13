<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DeclaringNamespace {

    use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
    use Symfony\Component\Routing\Attribute\Route;

    trait DeclaringTrait
    {
        /**
         * @RequiredPermissions(permissions={"ROLE_ADMIN"})
         */
        #[Route('/traits-in-two-namespaces', methods: ['GET'])]
        public function show()
        {
        }
    }
}

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\CollidingNamespace {

    use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherLibrary\Query as RequiredPermissions;

    trait CollidingTrait
    {
    }
}
