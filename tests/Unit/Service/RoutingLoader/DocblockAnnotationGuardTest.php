<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader;

use ArrayObject;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RoutingLoader\DocblockAnnotationGuard;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedFullyQualifiedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedNamespaceAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedNamespaceDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AliasedTraitMethodController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AppAnnotationFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeDocblockReferencesController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\AttributeOptionsWithNoteTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\BackslashBeforeTagDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildImportingForParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ChildOfImportingParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassAndActionDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockAndMethodAttributeController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ClassDocblockFullyQualifiedController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsOnAttributeRouteController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DocblockOptionsTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\DoubleBackslashDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\FullyQualifiedOptionsTrait;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\GroupUseDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ImportingParentController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseAnnotationImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\LowercaseFullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentInUnrelatedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MissingParentTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\MisspeltDocblockAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NamespaceMentionedInStringController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\NestedTraitActionController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionNextToAnnotatedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnActionNextToNestedTraitController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\OwnNamespaceAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\ParentWithoutImportController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitAliasAppAnnotationController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\SplitFullyQualifiedDocblockController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TagNamedLikeImportsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\TraitDocblockOptionsController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnimportedNoteTagController;
use Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Fixtures\UnusedAnnotationImportController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class DocblockAnnotationGuardTest extends TestCase
{
    private const ANNOTATION_NAMESPACE = 'Paysera\Bundle\ApiBundle\Annotation';
    private const GENERATED_NAMESPACE = 'Paysera\Bundle\ApiBundle\Tests\Unit\Service\RoutingLoader\Generated';
    private const NOT_READ_REASON = 'docblock annotations of the bundle are not read here, because Symfony passes no'
        . ' annotation reader to the route loader; use the attributes of the bundle instead';
    private const NOT_READ = ', but ' . self::NOT_READ_REASON;
    private const OTHER_LIBRARY = ' (for an annotation of another library named like one of the bundle\'s, import its'
        . ' class under another alias)';

    /**
     * @var string[]
     */
    private static $generatedFiles = [];

    public static function tearDownAfterClass(): void
    {
        foreach (self::$generatedFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$generatedFiles = [];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4?: string}>
     */
    public static function refusedProvider(): array
    {
        $imports = 'imports ' . self::ANNOTATION_NAMESPACE . '\\';
        $fullyQualifiedTag = 'has the docblock tag @\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions';

        return [
            'import of an annotation' => [
                DocblockOptionsOnAttributeRouteController::class,
                'show',
                DocblockOptionsOnAttributeRouteController::class,
                $imports . 'RequiredPermissions',
            ],
            'imports of two annotations, on the class and the action' => [
                ClassAndActionDocblockController::class,
                'show',
                ClassAndActionDocblockController::class,
                $imports . 'RequiredPermissions',
            ],
            'import with a group use statement' => [
                GroupUseDocblockController::class,
                'show',
                GroupUseDocblockController::class,
                $imports . 'RequiredPermissions',
            ],
            'import of the annotation namespace with an alias' => [
                MisspeltDocblockAnnotationController::class,
                'show',
                MisspeltDocblockAnnotationController::class,
                'imports ' . self::ANNOTATION_NAMESPACE,
            ],
            'import of an annotation that no docblock uses' => [
                UnusedAnnotationImportController::class,
                'show',
                UnusedAnnotationImportController::class,
                $imports . 'Body',
            ],
            'import of an annotation written in lowercase' => [
                LowercaseAnnotationImportController::class,
                'show',
                LowercaseAnnotationImportController::class,
                'imports paysera\bundle\apibundle\annotation\RequiredPermissions',
            ],
            'import of an annotation next to a tag that is not an annotation' => [
                UnimportedNoteTagController::class,
                'show',
                UnimportedNoteTagController::class,
                $imports . 'RequiredPermissions',
            ],
            'import of an annotation on the class, next to an attribute on the action' => [
                ClassDocblockAndMethodAttributeController::class,
                'update',
                ClassDocblockAndMethodAttributeController::class,
                $imports . 'RequiredPermissions',
            ],
            'tag through an alias of a parent namespace' => [
                AliasedNamespaceDocblockController::class,
                'show',
                AliasedNamespaceDocblockController::class,
                'has the docblock tag @Api\Annotation\RequiredPermissions',
                self::OTHER_LIBRARY,
            ],
            'fully qualified tag' => [
                FullyQualifiedDocblockController::class,
                'show',
                FullyQualifiedDocblockController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified tag on the class' => [
                ClassDocblockFullyQualifiedController::class,
                'show',
                ClassDocblockFullyQualifiedController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified tag split across docblock lines' => [
                SplitFullyQualifiedDocblockController::class,
                'show',
                SplitFullyQualifiedDocblockController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified tag with two leading backslashes' => [
                DoubleBackslashDocblockController::class,
                'show',
                DoubleBackslashDocblockController::class,
                'has the docblock tag @\\\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions',
            ],
            'fully qualified tag after a line of prose ending with a backslash' => [
                BackslashBeforeTagDocblockController::class,
                'show',
                BackslashBeforeTagDocblockController::class,
                $fullyQualifiedTag,
            ],
            'fully qualified tag written in lowercase, without arguments' => [
                LowercaseFullyQualifiedDocblockController::class,
                'show',
                LowercaseFullyQualifiedDocblockController::class,
                'has the docblock tag @\paysera\bundle\apibundle\annotation\validation',
            ],
            'tag through an alias that is not imported, in the parent declaring the action' => [
                ChildImportingForParentController::class,
                'show',
                ParentWithoutImportController::class,
                'has the docblock tag @Rest\RequiredPermissions',
                self::OTHER_LIBRARY,
            ],
            'import in the trait declaring the action' => [
                TraitDocblockOptionsController::class,
                'show',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'import in a trait used by another trait' => [
                NestedTraitActionController::class,
                'show',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'import in a nested trait, for an own action next to it' => [
                OwnActionNextToNestedTraitController::class,
                'index',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'import in a trait, for an own action next to it' => [
                OwnActionNextToAnnotatedTraitController::class,
                'index',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'import in the trait of an action renamed with as' => [
                AliasedTraitMethodController::class,
                'index',
                DocblockOptionsTrait::class,
                $imports . 'RequiredPermissions',
            ],
            'fully qualified tag in the trait of an action renamed with as' => [
                AliasedFullyQualifiedTraitMethodController::class,
                'index',
                FullyQualifiedOptionsTrait::class,
                $fullyQualifiedTag,
            ],
            'import in the parent declaring the action' => [
                ChildOfImportingParentController::class,
                'index',
                ImportingParentController::class,
                $imports . 'RequiredPermissions',
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1?: string}>
     */
    public static function passingProvider(): array
    {
        return [
            'attribute' => [AttributeOptionsController::class],
            'attribute next to a tag that is not an annotation' => [AttributeOptionsWithNoteTagController::class],
            'attribute through an alias of the bundle namespace' => [AliasedNamespaceAttributeController::class],
            'docblock references to the attribute classes, a tag of another library, a string and a closure' => [
                AttributeDocblockReferencesController::class,
            ],
            'tags named like imports that are not annotations' => [TagNamedLikeImportsController::class],
            'tag-shaped text in a string and a comment' => [NamespaceMentionedInStringController::class],
            'tag of another class outside the route\'s docblocks' => [
                MissingParentInUnrelatedDocblockController::class,
            ],
            'tag of another class that cannot be loaded' => [MissingParentTagController::class],
            'own action of a child whose parent imports an annotation' => [
                ChildOfImportingParentController::class,
            ],
            'annotation class of the application, imported: not detected' => [AppAnnotationController::class],
            'annotation class of the application, fully qualified: not detected' => [
                AppAnnotationFullyQualifiedController::class,
            ],
            'annotation class of the application, in the namespace of the controller: not detected' => [
                OwnNamespaceAppAnnotationController::class,
            ],
            'annotation class of the application, through a lowercase alias: not detected' => [
                LowercaseAliasAppAnnotationController::class,
            ],
            'annotation class of the application, through an alias of a parent namespace: not detected' => [
                NamespaceAliasAppAnnotationController::class,
            ],
            'annotation class of the application, split across lines through an alias: not detected' => [
                SplitAliasAppAnnotationController::class,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4?: string}>
     */
    public static function generatedRefusedProvider(): array
    {
        $tag = '@\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions(permissions={"ROLE_ADMIN"})';
        $fullyQualifiedTag = 'has the docblock tag @\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions';

        return [
            'Latin-1 prose before a fully qualified tag' => [
                'GeneratedLatin1ProseController',
                '',
                "R\xE9sum\xE9 of the exports\n     * " . $tag,
                $fullyQualifiedTag,
            ],
            'Latin-1 prose after a fully qualified tag' => [
                'GeneratedLatin1ProseAfterTagController',
                '',
                $tag . "\n     * R\xE9sum\xE9 of the exports",
                $fullyQualifiedTag,
            ],
            'Latin-1 prose ending with a backslash before a fully qualified tag' => [
                'GeneratedLatin1BackslashProseController',
                '',
                "R\xE9sum\xE9 under C:\\exports\\\n     * " . $tag,
                $fullyQualifiedTag,
            ],
            'Latin-1 prose before a tag split across lines at a no-break space' => [
                'GeneratedLatin1ProseSplitTagController',
                '',
                "R\xE9sum\xE9 of the exports\n     * @\\Paysera\\Bundle\\\n"
                . "     *\xC2\xA0ApiBundle\\Annotation\\RequiredPermissions(permissions={\"ROLE_ADMIN\"})",
                $fullyQualifiedTag,
            ],
            'fully qualified tag split across lines at a no-break space' => [
                'GeneratedNoBreakSpaceSplitController',
                '',
                "@\\Paysera\\Bundle\\\n"
                . "     *\u{00A0}ApiBundle\\Annotation\\RequiredPermissions(permissions={\"ROLE_ADMIN\"})",
                $fullyQualifiedTag,
            ],
            'fully qualified tag right after a quoted string of another tag' => [
                'GeneratedTagAfterStringController',
                '',
                '@deprecated "x"' . $tag,
                $fullyQualifiedTag,
            ],
            'fully qualified tag quoted in prose' => [
                'GeneratedQuotedTagController',
                '',
                '"@\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions" is gone',
                $fullyQualifiedTag,
            ],
            'fully qualified tag at the start of a docblock line' => [
                'GeneratedLineStartTagController',
                '',
                "\n" . $tag,
                $fullyQualifiedTag,
            ],
            'import of an annotation in a comma-separated use statement' => [
                'GeneratedCommaImportController',
                'use Psr\Log\LoggerInterface, ' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions as Perm;',
                '@Perm(permissions={"ROLE_ADMIN"})',
                'imports ' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions',
            ],
            'tag through an alias of a parent namespace spelt with a long s' => [
                'GeneratedLongSAliasController',
                "use Paysera\\Bundle\\ApiBundle as Api\u{017F};",
                "@Api\u{017F}\\Annotation\\RequiredPermissions(permissions={\"ROLE_ADMIN\"})",
                "has the docblock tag @Api\u{017F}\\Annotation\\RequiredPermissions",
                self::OTHER_LIBRARY,
            ],
            'tag through an alias of a parent namespace spelt with a Kelvin sign' => [
                'GeneratedKelvinAliasController',
                "use Paysera\\Bundle\\ApiBundle as Api\u{212A};",
                "@Api\u{212A}\\Annotation\\Validation",
                "has the docblock tag @Api\u{212A}\\Annotation\\Validation",
                self::OTHER_LIBRARY,
            ],
            'fully qualified tag name followed by a dash and a digit' => [
                'GeneratedDashDigitTagController',
                '',
                '@\\' . self::ANNOTATION_NAMESPACE . '\Validation-1',
                'has the docblock tag @\\' . self::ANNOTATION_NAMESPACE . '\Validation',
            ],
            'tag whose alias has a non-ASCII letter Doctrine does not accept' => [
                'GeneratedUmlautAliasController',
                '',
                "@\u{00DC}nicode\\Query(type=\"String\")",
                "has the docblock tag @\u{00DC}nicode\\Query",
                self::OTHER_LIBRARY,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function generatedPassingProvider(): array
    {
        $name = '@\\' . self::ANNOTATION_NAMESPACE . '\RequiredPermissions';

        return [
            'fully qualified tag name followed by a dash' => [
                'GeneratedDashedTagController',
                '',
                $name . '-only(permissions={"ROLE_ADMIN"})',
            ],
            'fully qualified tag in backticks in prose' => [
                'GeneratedBacktickProseController',
                '',
                'Replaces the `' . $name . '(permissions={"ROLE_ADMIN"})` of 1.x',
            ],
            'fully qualified tag in parentheses in prose' => [
                'GeneratedParenthesisProseController',
                '',
                '(' . $name . ' is gone)',
            ],
            'fully qualified tag in braces in prose' => [
                'GeneratedBraceProseController',
                '',
                '{' . $name . '}',
            ],
            'fully qualified tag right after a single-quoted string' => [
                'GeneratedTagAfterSingleQuotedStringController',
                '',
                "@deprecated 'x'" . $name . '(permissions={"ROLE_ADMIN"})',
            ],
        ];
    }

    /**
     * @dataProvider refusedProvider
     */
    public function testFailOnDocblockAnnotationsRefuses(
        string $controller,
        string $method,
        string $fileOwner,
        string $finding,
        string $remedy = ''
    ): void {
        $this->assertRefusedWith(
            $this->buildRefusalMessage($controller, $method, $fileOwner, $finding) . $remedy,
            $controller,
            $method
        );
    }

    /**
     * @dataProvider generatedRefusedProvider
     */
    public function testFailOnDocblockAnnotationsRefusesAGeneratedController(
        string $shortName,
        string $imports,
        string $docblock,
        string $finding,
        string $remedy = ''
    ): void {
        $controller = $this->declareController($shortName, $docblock, $imports);

        $this->assertRefusedWith(
            $this->buildRefusalMessage($controller, 'show', $controller, $finding) . $remedy,
            $controller
        );
    }

    /**
     * @dataProvider generatedPassingProvider
     */
    public function testFailOnDocblockAnnotationsPassesAGeneratedController(
        string $shortName,
        string $imports,
        string $docblock
    ): void {
        $this->check($this->declareController($shortName, $docblock, $imports));

        $this->addToAssertionCount(1);
    }

    public function testFailOnDocblockAnnotationsNamesTheRemedyForAnotherLibrary(): void
    {
        $controller = $this->declareController('GeneratedOtherLibraryTagController', '@GQL\Query(type="String")');

        $this->assertRefusedWith(
            $this->buildRefusalMessage($controller, 'show', $controller, 'has the docblock tag @GQL\Query')
            . self::OTHER_LIBRARY,
            $controller
        );
    }

    /**
     * @dataProvider passingProvider
     */
    public function testFailOnDocblockAnnotationsPasses(string $controller, string $method = 'show'): void
    {
        $this->check($controller, $method);

        $this->addToAssertionCount(1);
    }

    public function testFailOnDocblockAnnotationsRefusesEveryAnnotationOfTheBundle(): void
    {
        $annotationPattern = __DIR__ . '/../../../../src/Annotation/*.php';
        $annotationFiles = glob($annotationPattern);
        if ($annotationFiles === false || $annotationFiles === []) {
            throw new RuntimeException('No annotation class found by the pattern ' . $annotationPattern);
        }

        $checked = 0;
        foreach ($annotationFiles as $file) {
            $annotation = basename($file, '.php');
            if (interface_exists(self::ANNOTATION_NAMESPACE . '\\' . $annotation)) {
                continue;
            }

            $tag = '@\\' . self::ANNOTATION_NAMESPACE . '\\' . $annotation;
            $controller = $this->declareController('Generated' . $annotation . 'Controller', $tag);
            try {
                $this->check($controller);
                $this->fail('Expected a ConfigurationException for ' . $tag);
            } catch (ConfigurationException $exception) {
                $this->assertStringContainsString('has the docblock tag ' . $tag, $exception->getMessage());
            }
            $checked++;
        }

        $this->assertSame(count($annotationFiles) - 1, $checked);
    }

    public function testFailOnDocblockAnnotationsFailsWhenTheSourceCannotBeRead(): void
    {
        $controller = $this->declareController('GeneratedUnreadableController', '@note kept for older clients');
        $file = (new ReflectionClass($controller))->getFileName();
        unlink($file);

        $this->assertRefusedWith($this->buildUnreadableMessage($controller, 'show', $file), $controller);
    }

    public function testFailOnDocblockAnnotationsFailsWhenTheClassIsEvaluatedCode(): void
    {
        eval(sprintf(
            'namespace %s; class EvaluatedController { public function show(): void {} }',
            self::GENERATED_NAMESPACE
        ));
        $controller = self::GENERATED_NAMESPACE . '\EvaluatedController';

        $this->assertRefusedWith(
            $this->buildUnreadableMessage($controller, 'show', (new ReflectionClass($controller))->getFileName()),
            $controller
        );
    }

    public function testFailOnDocblockAnnotationsFailsWhenTheClassHasNoSourceFile(): void
    {
        $this->assertRefusedWith(
            $this->buildUnreadableMessage(ArrayObject::class, 'count', 'the source of ' . ArrayObject::class),
            ArrayObject::class,
            'count'
        );
    }

    public function testFailOnDocblockAnnotationsFailsWhenTheDocblockCannotBeMatched(): void
    {
        $jit = ini_get('pcre.jit');
        $backtrackLimit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.jit', '0');
        ini_set('pcre.backtrack_limit', '1');
        try {
            $this->check(AttributeOptionsWithNoteTagController::class);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertStringContainsString(
                'has a docblock that cannot be checked (preg error ' . PREG_BACKTRACK_LIMIT_ERROR . ')',
                $exception->getMessage()
            );
        } finally {
            ini_set('pcre.jit', (string)$jit);
            ini_set('pcre.backtrack_limit', (string)$backtrackLimit);
        }
    }

    private function buildRefusalMessage(string $controller, string $method, string $fileOwner, string $finding): string
    {
        return sprintf(
            '%s::%s(): %s %s%s',
            $controller,
            (new ReflectionMethod($controller, $method))->getName(),
            (new ReflectionClass($fileOwner))->getFileName(),
            $finding,
            self::NOT_READ
        );
    }

    private function buildUnreadableMessage(string $controller, string $method, string $source): string
    {
        return sprintf(
            '%s::%s(): %s cannot be read, so its docblocks cannot be checked for annotations of the bundle; %s',
            $controller,
            $method,
            $source,
            self::NOT_READ_REASON
        );
    }

    private function declareController(string $shortName, string $docblock, string $imports = ''): string
    {
        $placeholder = tempnam(sys_get_temp_dir(), 'guard');
        if ($placeholder === false) {
            throw new RuntimeException('Cannot create a temporary file');
        }
        $file = $placeholder . '.php';
        unlink($placeholder);
        self::$generatedFiles[] = $file;
        file_put_contents($file, sprintf(
            "<?php\nnamespace %s;\n%sclass %s\n{\n    /**\n     * %s\n     */\n"
            . "    public function show(): void\n    {\n    }\n}\n",
            self::GENERATED_NAMESPACE,
            $imports === '' ? '' : $imports . "\n",
            $shortName,
            $docblock
        ));
        require $file;

        return self::GENERATED_NAMESPACE . '\\' . $shortName;
    }

    private function assertRefusedWith(string $message, string $controller, string $method = 'show'): void
    {
        try {
            $this->check($controller, $method);
            $this->fail('Expected a ConfigurationException');
        } catch (ConfigurationException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }

    private function check(string $controller, string $method = 'show'): void
    {
        (new DocblockAnnotationGuard())->failOnDocblockAnnotations(
            new ReflectionClass($controller),
            new ReflectionMethod($controller, $method)
        );
    }
}
