<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Helper;

use Symfony\Component\HttpKernel\HttpKernelInterface;

class HttpKernelHelper
{
    public static function getMainRequestConstValue(): int
    {
        if (defined(HttpKernelInterface::class . 'MAIN_REQUEST')) {
            return HttpKernelInterface::MAIN_REQUEST;
        }

        return HttpKernelInterface::MASTER_REQUEST;
    }
}
