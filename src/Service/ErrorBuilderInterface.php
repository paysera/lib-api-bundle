<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service;

use Exception;
use Paysera\Bundle\RestBundle\Entity\Error;

interface ErrorBuilderInterface
{
    public function createErrorFromException(Exception $exception): Error;
}
