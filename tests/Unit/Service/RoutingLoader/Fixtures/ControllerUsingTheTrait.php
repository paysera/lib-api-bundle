<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures;

// the trait's own import of Query wins for its method, as in Doctrine
use Paysera\Bundle\ApiBundle\Annotation\Body as Query;

class ControllerUsingTheTrait
{
    use TraitWithDocblockOptions;
}
