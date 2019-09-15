<?php
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Service\Doctrine;

use Doctrine\Common\Persistence\ObjectRepository;
use Paysera\Bundle\RestBundle\Annotation\PathAttribute;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\MixedTypeDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;

class FindEntityDenormalizer implements MixedTypeDenormalizerInterface, TypeAwareInterface
{
    private $repository;
    private $searchField;

    public function __construct(ObjectRepository $repository, string $searchField)
    {
        $this->repository = $repository;
        $this->searchField = $searchField;
    }

    public function denormalize($input, DenormalizationContext $context)
    {
        return $this->repository->findOneBy(
            [$this->searchField => $input]
        );
    }

    public function getType(): string
    {
        return sprintf(
            '%s%s',
            $this->repository->getClassName(),
            PathAttribute::DENORMALIZATION_TYPE_POSTFIX
        );
    }
}
