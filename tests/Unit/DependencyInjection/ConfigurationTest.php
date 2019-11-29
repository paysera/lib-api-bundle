<?php

declare(strict_types=1);

namespace Paysera\Bundle\RestBundle\Tests\Unit\DependencyInjection;

use Paysera\Bundle\RestBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends TestCase
{
    /**
     * @param array $expected expected parsed configuration
     * @param string $configFilename filename in Fixtures directory for yaml file to load
     *
     * @dataProvider configurationTestCaseProvider
     */
    public function testGetConfigTreeBuilder(array $expected, string $configFilename)
    {
        $this->assertEquals($expected, $this->processForFile($configFilename));
    }

    /**
     * @dataProvider invalidConfigurationTestCaseProvider
     * @param string $configFilename
     */
    public function testInvalidConfiguration(string $configFilename)
    {
        try {
            $this->processForFile($configFilename);
        } catch (InvalidConfigurationException $exception) {
            $this->addToAssertionCount(1);
            return;
        }
        $this->fail('Configuration processing should have failed');
    }

    private function processForFile($configFilename)
    {
        $fullConfiguration = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/' . $configFilename));
        $configuration = new Configuration();
        $processor = new Processor();
        return $processor->processConfiguration($configuration, [$fullConfiguration['paysera_rest']]);
    }

    public function configurationTestCaseProvider()
    {
        return [
            'Parses full structure' => [
                [
                    'locales' => ['en', 'lt', 'lv'],
                    'validation' => [
                        'property_path_converter' => 'your_service_id',
                    ],
                    'path_attribute_resolvers' => [
                        'App\Entity\PersistedEntity' => [
                            'field' => 'identifierField',
                        ],
                        'App\Entity\OtherEntity' => [
                            'field' => 'id',
                        ],
                    ],
                    'pagination' => [
                        'total_count_strategy' => 'always',
                        'maximum_offset' => 999,
                        'maximum_limit' => 888,
                        'default_limit' => 777,
                    ],
                ],
                'full.yaml',
            ],
            'Parses empty' => [
                [
                    'locales' => [],
                    'validation' => [
                        'property_path_converter' => null,
                    ],
                    'path_attribute_resolvers' => [],
                    'pagination' => [
                        'total_count_strategy' => 'optional',
                        'maximum_offset' => 1000,
                        'maximum_limit' => 1000,
                        'default_limit' => 100,
                    ],
                ],
                'empty.yaml',
            ],
        ];
    }

    public function invalidConfigurationTestCaseProvider()
    {
        return [
            ['invalid/pagination_no_maximum_limit.yaml'],
            ['invalid/pagination_invalid_strategy.yaml'],
            ['invalid/pagination_default_limit_too_large.yaml'],
        ];
    }
}
