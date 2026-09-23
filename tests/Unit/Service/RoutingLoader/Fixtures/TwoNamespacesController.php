<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OtherNamespace {
    use Paysera\Bundle\ApiBundle\Annotation\Body as CustomRestAnnotation;
}

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures {
    class TwoNamespacesController
    {
        /**
         * @CustomRestAnnotation
         */
        public function show()
        {
        }
    }
}
