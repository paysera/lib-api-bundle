<?php
declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Paysera\Bundle\ApiBundle\Tests\Functional\Fixtures\FixtureTestBundle\Repository\PersistedEntityRepository")
 */
class PersistedEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="integer")
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string")
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
