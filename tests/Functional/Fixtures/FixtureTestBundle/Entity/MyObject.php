<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Entity;

class MyObject
{
    /**
     * @var string|null
     */
    private $field1;

    /**
     * @var string|null
     */
    private $internalField1;

    public function __toString()
    {
        return 'MyObject:' . $this->field1 . $this->internalField1;
    }

    /**
     * @return string|null
     */
    public function getField1(): string
    {
        return $this->field1;
    }

    /**
     * @param string|null $field1
     * @return $this
     */
    public function setField1(string $field1): self
    {
        $this->field1 = $field1;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalField1()
    {
        return $this->internalField1;
    }

    /**
     * @param string|null $internalField1
     * @return $this
     */
    public function setInternalField1($internalField1): self
    {
        $this->internalField1 = $internalField1;
        return $this;
    }
}
