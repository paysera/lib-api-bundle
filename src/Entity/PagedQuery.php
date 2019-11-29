<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Entity;

use Paysera\Pagination\Entity\Doctrine\ConfiguredQuery;
use Paysera\Pagination\Entity\Pager;

class PagedQuery
{
    const TOTAL_COUNT_STRATEGY_DEFAULT = 'default';
    const TOTAL_COUNT_STRATEGY_ALWAYS = 'always';
    const TOTAL_COUNT_STRATEGY_OPTIONAL = 'optional';
    const TOTAL_COUNT_STRATEGY_NEVER = 'never';

    /**
     * @var ConfiguredQuery
     */
    private $configuredQuery;

    /**
     * @var Pager
     */
    private $pager;

    /**
     * @var string
     */
    private $totalCountStrategy;

    public function __construct(ConfiguredQuery $configuredQuery, Pager $pager)
    {
        $this->configuredQuery = $configuredQuery;
        $this->pager = $pager;
        $this->totalCountStrategy = self::TOTAL_COUNT_STRATEGY_DEFAULT;
    }

    public function getConfiguredQuery(): ConfiguredQuery
    {
        return $this->configuredQuery;
    }

    public function getPager(): Pager
    {
        return $this->pager;
    }

    public function getTotalCountStrategy(): string
    {
        return $this->totalCountStrategy;
    }

    public function setTotalCountStrategy(string $totalCountStrategy): self
    {
        $this->totalCountStrategy = $totalCountStrategy;
        return $this;
    }
}
