<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service;

use Exception;
use Paysera\Bundle\ApiBundle\Entity\Error;

interface ErrorBuilderInterface
{
    public function createErrorFromException(Exception $exception): Error;
}
