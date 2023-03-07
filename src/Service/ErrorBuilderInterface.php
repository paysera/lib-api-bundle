<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service;

use Paysera\Bundle\ApiBundle\Entity\Error;
use Throwable;

interface ErrorBuilderInterface
{
    public function createErrorFromException(Throwable $exception): Error;
}
