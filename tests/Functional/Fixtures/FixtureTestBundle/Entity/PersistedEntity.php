<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity;

class PersistedEntity
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $someField;

    /**
     * @return int|null
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSomeField(): string
    {
        return $this->someField;
    }

    /**
     * @param string|null $someField
     * @return $this
     */
    public function setSomeField(string $someField): self
    {
        $this->someField = $someField;
        return $this;
    }
}
