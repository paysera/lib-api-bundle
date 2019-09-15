<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\Validation;

class CamelCaseToSnakeCaseConverter implements PropertyPathConverterInterface
{
    public function convert($path)
    {
        return ltrim(
            mb_strtolower(
                preg_replace(
                    '/[A-Z]/u',
                    '_$0',
                    $path
                )
            ),
            '_'
        );
    }
}
