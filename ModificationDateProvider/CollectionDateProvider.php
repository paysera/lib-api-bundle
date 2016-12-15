<?php

namespace Paysera\Bundle\RestBundle\ModificationDateProvider;

use Paysera\Bundle\RestBundle\Cache\ModificationDateProviderInterface;

class CollectionDateProvider implements ModificationDateProviderInterface
{
    protected $itemDateProvider;

    public function __construct(ModificationDateProviderInterface $itemDateProvider)
    {
        $this->itemDateProvider = $itemDateProvider;
    }

    /**
     * @param array $items
     *
     * @return \DateTime|null
     */
    public function getModifiedAt($items)
    {
        $lastDate = null;
        foreach ($items as $item) {
            $itemDate = $this->itemDateProvider->getModifiedAt($item);
            if ($itemDate !== null && ($lastDate === null || $itemDate > $lastDate)) {
                $lastDate = $itemDate;
            }
        }
        return $lastDate;
    }
} 
