<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\PathAttributeResolver;

use InvalidArgumentException;

class PathAttributeResolverRegistry
{
    /**
     * @var array|PathAttributeResolverInterface[] by type
     */
    private $resolvers;

    public function __construct()
    {
        $this->resolvers = [];
    }

    public function registerPathAttributeResolver(PathAttributeResolverInterface $resolver, string $type)
    {
        $this->resolvers[$type] = $resolver;
    }

    public function getResolverByType(string $type): PathAttributeResolverInterface
    {
        if (!isset($this->resolvers[$type])) {
            throw new InvalidArgumentException(
                sprintf('No such path attribute resolver registered: "%s"', $type)
            );
        }

        return $this->resolvers[$type];
    }
}
