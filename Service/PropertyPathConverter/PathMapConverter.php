<?php

namespace Paysera\Bundle\RestBundle\Service\PropertyPathConverter;

use Paysera\Component\Serializer\Validation\PropertyPathConverterInterface;

class PathMapConverter implements PropertyPathConverterInterface
{
    private $pathMapProvider;

    public function __construct(PathMapProviderInterface $pathMapProvider)
    {
        $this->pathMapProvider = $pathMapProvider;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function convert($path)
    {
        $map = $this->pathMapProvider->getPathMap();

        return isset($map[$path])
            ? $map[$path]
            : $path
        ;
    }
}
