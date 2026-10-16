<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\DependencyInjection;

use Paysera\Bundle\ApiBundle\DependencyInjection\PayseraApiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as HttpKernelExtension;

class PayseraApiExtensionTest extends TestCase
{
    /**
     * @dataProvider loadDataProvider
     * @param array<array<string, mixed>> $configs
     * @param array<string, mixed> $expected
     */
    public function testLoad(array $configs, array $expected): void
    {
        $container = new ContainerBuilder();
        $empty = new ContainerBuilder();

        (new PayseraApiExtension())->load($configs, $container);

        $definitions = array_diff_key($container->getDefinitions(), $empty->getDefinitions());
        ksort($definitions);
        $aliases = array_diff_key($container->getAliases(), $empty->getAliases());
        ksort($aliases);
        $this->assertSame($expected, [
            'definitions' => array_map(
                function (Definition $definition): array {
                    return $this->dumpDefinition($definition, false);
                },
                $definitions
            ),
            'aliases' => array_map(
                static function (Alias $alias): array {
                    return ['id' => (string)$alias, 'public' => $alias->isPublic()];
                },
                $aliases
            ),
            'parameters' => $container->getParameterBag()->all(),
        ]);
    }

    public static function loadDataProvider(): array
    {
        $definitions = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/services.json'), true);
        unset($definitions[
            class_exists(AttributeRouteControllerLoader::class)
                ? 'paysera_api.annotations.loader'
                : 'paysera_api.loader.attribute'
        ]);
        $withoutLocaleListener = $definitions;
        unset($withoutLocaleListener['paysera_api.listener.locale']);
        $withDocblockAnnotationsTurnedOn = $withoutLocaleListener;
        if (isset($withDocblockAnnotationsTurnedOn['paysera_api.loader.attribute'])) {
            $withDocblockAnnotationsTurnedOn['paysera_api.loader.attribute']['calls'][] = [
                'setDocblockAnnotationsEnabled',
                [true],
            ];
        }
        $pagination = [
            'paysera_api.pagination.default_total_count_strategy' => 'optional',
            'paysera_api.pagination.maximum_offset' => 1000,
            'paysera_api.pagination.default_limit' => 100,
            'paysera_api.pagination.maximum_limit' => 1000,
        ];

        return [
            'no configuration' => [
                [],
                [
                    'definitions' => $withoutLocaleListener,
                    'aliases' => [
                        'paysera_api.validation.property_path_converter' => [
                            'id' => 'paysera_api.camel_case_to_snake_case_converter',
                            'public' => false,
                        ],
                    ],
                    'parameters' => ['paysera_api.locales' => []] + $pagination,
                ],
            ],
            'docblock annotations turned on' => [
                [['docblock_annotations' => true]],
                [
                    'definitions' => $withDocblockAnnotationsTurnedOn,
                    'aliases' => [
                        'paysera_api.validation.property_path_converter' => [
                            'id' => 'paysera_api.camel_case_to_snake_case_converter',
                            'public' => false,
                        ],
                    ],
                    'parameters' => ['paysera_api.locales' => []] + $pagination,
                ],
            ],
            'locales and a property path converter' => [
                [['locales' => ['en', 'lt'], 'validation' => ['property_path_converter' => 'app.converter']]],
                [
                    'definitions' => $definitions,
                    'aliases' => [
                        'paysera_api.validation.property_path_converter' => [
                            'id' => 'app.converter',
                            'public' => (new Alias('app.converter'))->isPublic(),
                        ],
                    ],
                    'parameters' => ['paysera_api.locales' => ['en', 'lt']] + $pagination,
                ],
            ],
        ];
    }

    public function testExtensionDoesNotExtendHttpKernelsInternalExtension(): void
    {
        $this->assertNotInstanceOf(HttpKernelExtension::class, new PayseraApiExtension());
    }

    public function testLoadingTriggersNoDeprecation(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);
        try {
            (new PayseraApiExtension())->load([], new ContainerBuilder());
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }

    /**
     * @return array<string, mixed>
     */
    private function dumpDefinition(Definition $definition, bool $inline): array
    {
        return array_filter(
            [
                'class' => $definition->getClass(),
                'parent' => $definition instanceof ChildDefinition ? $definition->getParent() : null,
                'public' => $inline || $definition->isPublic() === (new Definition())->isPublic()
                    ? null
                    : $definition->isPublic(),
                'shared' => $definition->isShared() ? null : false,
                'lazy' => $definition->isLazy() ?: null,
                'abstract' => $definition->isAbstract() ?: null,
                'synthetic' => $definition->isSynthetic() ?: null,
                'autowired' => $definition->isAutowired() ?: null,
                'autoconfigured' => $definition->isAutoconfigured() ?: null,
                'deprecated' => $definition->isDeprecated() ?: null,
                'factory' => $this->export($definition->getFactory()),
                'arguments' => $this->export($definition->getArguments()),
                'calls' => $this->export($definition->getMethodCalls()),
                'properties' => $this->export($definition->getProperties()),
                'configurator' => $this->export($definition->getConfigurator()),
                'tags' => $definition->getTags(),
                'decorated' => $definition->getDecoratedService(),
            ],
            static function ($value): bool {
                return $value !== null && $value !== [];
            }
        );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function export($value)
    {
        if ($value instanceof Reference) {
            return ['reference' => (string)$value, 'invalid_behavior' => $value->getInvalidBehavior()];
        }
        if ($value instanceof Definition) {
            return ['inline' => $this->dumpDefinition($value, true)];
        }
        if (is_array($value)) {
            return array_map([$this, 'export'], $value);
        }

        return $value;
    }
}
