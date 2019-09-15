<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Functional\Fixtures\FixtureTestBundle\Normalizer;

use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class ItemDenormalizer implements ObjectDenormalizerInterface
{
    private $item;

    public function __construct(string $item)
    {
        $this->item = $item;
    }

    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        return $input->getRequiredString($this->item);
    }
}
