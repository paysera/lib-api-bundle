<?php

namespace Paysera\Bundle\RestBundle\Service\PropertyPathConverter;

use Paysera\Component\Serializer\Validation\PropertyPathConverterInterface;

class PathConverter implements PropertyPathConverterInterface
{
    private $converters;

    /**
     * @param PropertyPathConverterInterface[] $converters
     */
    public function __construct(array $converters)
    {
        $this->converters = $converters;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function convert($path)
    {
        foreach ($this->converters as $converter) {
            $path = $converter->convert($path);
        }

        return $path;
    }
}
