<?php

namespace Paysera\Bundle\RestBundle\Repository;

use Paysera\Component\Serializer\Entity\Result;

interface CursorFilterAwareRepositoryInterface extends BaseFilterAwareRepositoryInterface
{
    /**
     * @param Result $result
     */
    public function populateResultCursors(Result $result);
}
