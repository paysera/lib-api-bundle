<?php

declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity;

class PersistedEntityFilter
{
    /**
     * @var string|null
     */
    private $someField;

    /**
     * @return string|null
     */
    public function getSomeField()
    {
        return $this->someField;
    }

    /**
     * @param string|null $someField
     * @return $this
     */
    public function setSomeField($someField): self
    {
        $this->someField = $someField;
        return $this;
    }
}
