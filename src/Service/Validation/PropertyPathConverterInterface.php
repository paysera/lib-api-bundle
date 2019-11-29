<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\Validation;

interface PropertyPathConverterInterface
{
    /**
     * @param string $path
     *
     * @return string
     */
    public function convert($path);
}
