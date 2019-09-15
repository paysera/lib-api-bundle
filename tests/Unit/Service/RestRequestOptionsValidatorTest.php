<?php
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\Service;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Paysera\Bundle\RestBundle\Entity\PathAttributeResolverOptions;
use Paysera\Bundle\RestBundle\Entity\QueryResolverOptions;
use Paysera\Bundle\RestBundle\Entity\RestRequestOptions;
use Paysera\Bundle\RestBundle\Exception\ConfigurationException;
use Paysera\Bundle\RestBundle\Service\RestRequestOptionsValidator;
use Paysera\Component\Normalization\NormalizerRegistryInterface;

class RestRequestOptionsValidatorTest extends MockeryTestCase
{
    /**
     * @dataProvider provideDataForValidateRestRequestOptions
     *
     * @param bool $expectException
     * @param RestRequestOptions $options
     * @throws ConfigurationException
     */
    public function testValidateRestRequestOptions(bool $expectException, RestRequestOptions $options)
    {
        /** @var MockInterface|NormalizerRegistryInterface $registryMock */
        $registryMock = Mockery::mock(NormalizerRegistryInterface::class);
        $validator = new RestRequestOptionsValidator(
            $registryMock
        );

        $registryMock
            ->shouldReceive('hasNormalizer')
            ->with('non_existing_normalizer')
            ->andReturn(false)
        ;
        $registryMock
            ->shouldReceive('hasNormalizer')
            ->with('existing_normalizer')
            ->andReturn(true)
        ;
        $registryMock
            ->shouldReceive('getDenormalizerType')
            ->with('non_existing_denormalizer')
            ->andReturn(NormalizerRegistryInterface::DENORMALIZER_TYPE_NONE)
        ;
        $registryMock
            ->shouldReceive('getDenormalizerType')
            ->with('object_denormalizer')
            ->andReturn(NormalizerRegistryInterface::DENORMALIZER_TYPE_OBJECT)
        ;
        $registryMock
            ->shouldReceive('getDenormalizerType')
            ->with('mixed_type_denormalizer')
            ->andReturn(NormalizerRegistryInterface::DENORMALIZER_TYPE_MIXED)
        ;

        if ($expectException) {
            $this->expectException(ConfigurationException::class);
        }

        $validator->validateRestRequestOptions($options, 'MyClass::method');

        if (!$expectException) {
            $this->addToAssertionCount(1);
        }
    }

    public function provideDataForValidateRestRequestOptions()
    {
        return [
            [
                false,
                (new RestRequestOptions())
                    ->setResponseNormalizationType('existing_normalizer')
                ,
            ],
            [
                true,
                (new RestRequestOptions())
                    ->setResponseNormalizationType('non_existing_normalizer')
                ,
            ],
            [
                false,
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('object_denormalizer')
                    ->setBodyParameterName('body')
                ,
            ],
            [
                false,
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('mixed_type_denormalizer')
                    ->setBodyParameterName('body')
                ,
            ],
            [
                true,
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('non_existing_denormalizer')
                    ->setBodyParameterName('body')
                ,
            ],
            [
                true,
                (new RestRequestOptions())
                    ->setBodyDenormalizationType('non_existing_denormalizer')
                    ->setBodyParameterName('body')
                    ->setBodyOptional(true)
                ,
            ],
            [
                false,
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                        ->setDenormalizationType('object_denormalizer')
                ),
            ],
            [
                false,
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                        ->setDenormalizationType('mixed_type_denormalizer')
                ),
            ],
            [
                true,
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                        ->setDenormalizationType('non_existing_denormalizer')
                ),
            ],
            [
                true,
                (new RestRequestOptions())->addQueryResolverOptions(
                    (new QueryResolverOptions())
                        ->setDenormalizationType('object_denormalizer')
                )->addQueryResolverOptions(
                    (new QueryResolverOptions())
                        ->setDenormalizationType('non_existing_denormalizer')
                ),
            ],
            [
                false,
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                        ->setDenormalizationType('mixed_type_denormalizer')
                ),
            ],
            [
                true,
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                        ->setDenormalizationType('object_denormalizer')
                ),
            ],
            [
                true,
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                        ->setDenormalizationType('non_existing_denormalizer')
                ),
            ],
            [
                true,
                (new RestRequestOptions())->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                        ->setDenormalizationType('mixed_type_denormalizer')
                )->addPathAttributeResolverOptions(
                    (new PathAttributeResolverOptions())
                        ->setDenormalizationType('non_existing_denormalizer')
                ),
            ],
        ];
    }
}
