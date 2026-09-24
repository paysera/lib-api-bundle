<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Finds the annotations for this bundle in the docblocks of a controller method and its class without an annotation
 * reader. It follows the rules of Doctrine's reader, which applied them on Symfony 4.4 to 6.4:
 * - the imports are the `use` statements of the class's file above the class, in the class's namespace; a method
 *   declared in a trait also gets those of the trait's file (Doctrine's PhpParser, TokenParser, getMethodImports());
 * - reading starts at the first "@" after a space, a tab or "*"; from there an annotation starts at an "@" after
 *   whitespace or "*", a quoted string is text, a name followed by "-" is not an annotation (unless the "-" starts a
 *   number), and the arguments of an annotation are skipped (Doctrine's DocParser and DocLexer);
 * - a name resolves through the imports, else relative to the namespace, else as a fully qualified name.
 * Doctrine also passes over the tag names it ignores (Target, Required, param and so on) unless they are imported or
 * written in full. This finder keeps no such list, so where it cannot tell, it reads on and reports more, never less.
 *
 * @internal
 */
class DocblockAnnotationFinder
{
    /**
     * A name as Doctrine's lexer reads it: letters, digits, "_", ":" and "\".
     */
    private const NAME = '[a-z_\\\\][a-z0-9_:\\\\]*[a-z_][a-z0-9_]*|[a-z_]';

    /**
     * A quoted string, in which "" stands for a quote.
     */
    private const STRING = '"(?:""|[^"])*+"';

    /**
     * A quoted string, which is one token, or an "@" at the start or after whitespace, "*" or a quote, with the name
     * right after it (group 1) and, when the name is followed by "-" that does not start a number, that "-" (group 2).
     * Doctrine's lexer measures a token that starts with a quote without its quotes, so an "@" right after a quote is
     * never glued to it.
     */
    private const TOKEN_PATTERN = '/' . self::STRING . '|(?<![^\s*"])@(' . self::NAME . ')(-(?![0-9]))?/iu';

    /**
     * The arguments after an annotation's name: the parentheses, after any whitespace or "*", up to the matching one.
     */
    private const ARGUMENTS_PATTERN = '/\G[\s*]*+(\((?:' . self::STRING . '|[^()"]++|"|(?1))*+\))/u';

    /**
     * @var array<string, array<string, string>> imports by class name
     */
    private $importsByClass = [];

    /**
     * @return string[] class names of the bundle annotations the docblocks use, in order, each once
     */
    public function findBundleAnnotations(ReflectionClass $class, ReflectionMethod $method): array
    {
        $declaringClass = $method->getDeclaringClass();
        $methodImports = $this->readImports($declaringClass);
        foreach ($declaringClass->getTraits() as $trait) {
            if ($trait->hasMethod($method->getName()) && $trait->getFileName() === $method->getFileName()) {
                $methodImports = array_merge($methodImports, $this->readImports($trait));
            }
        }

        $found = array_merge(
            $this->findInDocblock($class->getDocComment(), $this->readImports($class), $class->getNamespaceName()),
            $this->findInDocblock($method->getDocComment(), $methodImports, $declaringClass->getNamespaceName())
        );

        return array_values(array_unique($found));
    }

    /**
     * @param string|false $docComment
     * @param array<string, string> $imports
     * @return string[]
     */
    private function findInDocblock($docComment, array $imports, string $namespace): array
    {
        if ($docComment === false || preg_match('/[ \t*]@/', $docComment, $start, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }

        $text = substr($docComment, $start[0][1] + 1);
        $found = [];
        $offset = 0;
        while (preg_match(self::TOKEN_PATTERN, $text, $token, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $offset = $token[0][1] + strlen($token[0][0]);
            $isString = !isset($token[1]);
            $isFollowedByDash = isset($token[2]);
            if ($isString || $isFollowedByDash) {
                continue;
            }

            $name = $token[1][0];
            $importedName = $this->resolveImportedName($name, $imports);
            $candidates = $importedName !== null ? [$importedName] : [$namespace . '\\' . $name, $name];
            $className = $this->findClass($candidates);
            if ($className === null) {
                continue;
            }
            if (is_subclass_of($className, RestAnnotationInterface::class)) {
                $found[] = $className;
            }
            // Doctrine reads the arguments of an annotation class named through an import or in full, so an "@" in them
            // is a nested annotation or text. Found another way, the class may carry a name Doctrine ignores, and then
            // Doctrine reads what follows as top-level annotations: so do not skip.
            if ($importedName !== null
                && $this->isAnnotationClass($className)
                && preg_match(self::ARGUMENTS_PATTERN, $text, $arguments, 0, $offset) === 1
            ) {
                $offset += strlen($arguments[0]);
            }
        }

        return $found;
    }

    /**
     * @param array<string, string> $imports
     * @return string|null the class name a fully qualified or imported name stands for, or null for any other name
     */
    private function resolveImportedName(string $name, array $imports): ?string
    {
        if ($name[0] === '\\') {
            return $name;
        }

        $parts = explode('\\', $name, 2);
        $alias = strtolower($parts[0]);
        if (!isset($imports[$alias])) {
            return null;
        }

        return $imports[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
    }

    /**
     * @param string[] $candidates class names in the order Doctrine tries them
     * @return string|null the first that exists, as the class declares its name
     */
    private function findClass(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return (new ReflectionClass($candidate))->getName();
            }
        }

        return null;
    }

    private function isAnnotationClass(string $className): bool
    {
        return strpos((string)(new ReflectionClass($className))->getDocComment(), '@Annotation') !== false;
    }

    /**
     * @return array<string, string> imported class or namespace name by its lower-case alias
     */
    private function readImports(ReflectionClass $class): array
    {
        $className = $class->getName();
        if (!isset($this->importsByClass[$className])) {
            $this->importsByClass[$className] = $this->parseImports($class);
        }

        return $this->importsByClass[$className];
    }

    /**
     * The `use` statements of the class's file up to the class, from the class's namespace declaration on. So a
     * trait's `use` in a class body does not count, nor does a commented-out import or another namespace's import.
     *
     * @return array<string, string>
     */
    private function parseImports(ReflectionClass $class): array
    {
        $fileName = $class->getFileName();
        if ($fileName === false || !is_file($fileName)) {
            return [];
        }

        $source = implode('', array_slice(file($fileName), 0, $class->getStartLine()));
        $tokens = [];
        foreach (token_get_all($source) as $token) {
            if (!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $tokens[] = $token;
            }
        }

        $imports = [];
        foreach ($tokens as $index => $token) {
            if ($token[0] === T_USE) {
                $imports = array_merge($imports, $this->parseUseStatement($tokens, $index + 1));
            } elseif ($token[0] === T_NAMESPACE
                && $this->readName($tokens, $index + 1) === $class->getNamespaceName()
            ) {
                $imports = [];
            }
        }

        return $imports;
    }

    /**
     * One `use` statement: a name, a name with "as", names separated by commas, or a group ("use A\{B, C as D};").
     * `use function`, `use const` and a closure's `use (...)` import no class, so they end at their first token.
     *
     * @param array<int, string|array{0: int, 1: string, 2: int}> $tokens
     * @return array<string, string>
     */
    private function parseUseStatement(array $tokens, int $index): array
    {
        $imports = [];
        $groupPrefix = '';
        $name = '';
        $alias = '';
        $isAliasNext = false;
        for (; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            if ($this->isNameToken($token)) {
                if ($isAliasNext) {
                    $alias = $token[1];
                } else {
                    $name .= $token[1];
                    $parts = explode('\\', $token[1]);
                    $alias = end($parts);
                }
            } elseif ($token[0] === T_AS) {
                $isAliasNext = true;
            } elseif ($token === ',' || $token === ';') {
                $imports[strtolower($alias)] = $groupPrefix . $name;
                if ($token === ';') {
                    break;
                }
                $name = '';
                $alias = '';
                $isAliasNext = false;
            } elseif ($token === '{') {
                $groupPrefix = $name;
                $name = '';
            } elseif ($token !== '}') {
                break;
            }
        }

        return $imports;
    }

    /**
     * @param array<int, string|array{0: int, 1: string, 2: int}> $tokens
     */
    private function readName(array $tokens, int $index): string
    {
        $name = '';
        for (; isset($tokens[$index]) && $this->isNameToken($tokens[$index]); $index++) {
            $name .= $tokens[$index][1];
        }

        return $name;
    }

    /**
     * @param string|array{0: int, 1: string, 2: int} $token
     */
    private function isNameToken($token): bool
    {
        return is_array($token) && (
            in_array($token[0], [T_STRING, T_NS_SEPARATOR], true)
            // PHP 8 reads a qualified name as one token
            || (PHP_VERSION_ID >= 80000 && in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true))
        );
    }
}
