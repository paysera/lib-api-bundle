<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Finds the annotations for this bundle in the docblocks of a controller method and its class without an annotation
 * reader. Each docblock tag is resolved the way Doctrine's reader resolves it on Symfony 4.4 to 6.4: through the `use`
 * imports of the declaring class's file and of the file that declares the method (a trait's), then relative to the
 * class's namespace, then as a fully qualified name.
 *
 * @internal
 */
class DocblockAnnotationFinder
{
    /**
     * @var array<string, array<string, string>> imports by file name
     */
    private $importsByFile = [];

    /**
     * @return string[] class names of the bundle annotations the docblocks use, in order, each once
     */
    public function findBundleAnnotations(ReflectionClass $class, ReflectionMethod $method): array
    {
        $declaringClass = $method->getDeclaringClass();
        $found = array_merge(
            $this->findInDocblock($class->getDocComment(), [$class->getFileName()], $class->getNamespaceName()),
            $this->findInDocblock(
                $method->getDocComment(),
                [$declaringClass->getFileName(), $method->getFileName()],
                $declaringClass->getNamespaceName()
            )
        );

        return array_values(array_unique($found));
    }

    /**
     * @param string|false $docComment
     * @param array<string|false> $fileNames the files whose imports apply; a later file's import wins
     * @return string[]
     */
    private function findInDocblock($docComment, array $fileNames, string $namespace): array
    {
        if ($docComment === false || strpos($docComment, '@') === false) {
            return [];
        }

        // a top-level annotation, as Doctrine's lexer reads it: "@" at the start or after whitespace or "*", so
        // "{@inheritdoc}", "mail@host" and an annotation nested in another annotation's arguments do not count
        preg_match_all(
            '/(?:^|[\s*])@(\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*)/',
            $docComment,
            $matches
        );
        $imports = [];
        foreach (array_unique($fileNames) as $fileName) {
            $imports = array_merge($imports, $this->readImports($fileName));
        }

        $found = [];
        foreach ($matches[1] as $name) {
            $className = $this->resolveClassName($name, $imports, $namespace);
            if ($className !== null) {
                $found[] = $className;
            }
        }

        return $found;
    }

    /**
     * @param string|false $fileName false only for an internal class, which carries no docblocks
     * @return array<string, string> imported class or namespace name by its lower-case alias
     */
    private function readImports($fileName): array
    {
        $key = (string)$fileName;
        if (!isset($this->importsByFile[$key])) {
            $this->importsByFile[$key] = $this->parseImports(
                $fileName === false ? '' : (string)file_get_contents($fileName)
            );
        }

        return $this->importsByFile[$key];
    }

    /**
     * The `use` statements of a file: one name, "as" aliases, several names separated by commas, and a group
     * ("use A\{B, C as D};"); function and constant imports are left out. A trait's `use` inside a class body can add a
     * harmless alias of the trait's own name.
     *
     * @return array<string, string>
     */
    private function parseImports(string $source): array
    {
        preg_match_all('/(?:^|;)\s*use\s+(?!function\s|const\s)([\\\\A-Za-z_][^;]*);/mi', $source, $statements);

        $imports = [];
        foreach ($statements[1] as $statement) {
            $prefix = '';
            if (preg_match('/^([^{]*)\{([^}]*)\}\s*$/', trim($statement), $group) === 1) {
                $prefix = trim($group[1]);
                $statement = $group[2];
            }
            foreach (explode(',', $statement) as $clause) {
                $pattern = '/^\s*\\\\?([A-Za-z_][A-Za-z0-9_\\\\]*)(?:\s+as\s+([A-Za-z_][A-Za-z0-9_]*))?\s*$/i';
                if (preg_match($pattern, $clause, $match) !== 1) {
                    continue;
                }
                $importedName = ltrim($prefix . $match[1], '\\');
                $parts = explode('\\', $importedName);
                $alias = isset($match[2]) && $match[2] !== '' ? $match[2] : end($parts);
                $imports[strtolower($alias)] = $importedName;
            }
        }

        return $imports;
    }

    /**
     * @param array<string, string> $imports
     * @return string|null the bundle annotation class the tag names, or null when it names none
     */
    private function resolveClassName(string $name, array $imports, string $namespace): ?string
    {
        if ($name[0] === '\\') {
            $candidates = [substr($name, 1)];
        } else {
            $parts = explode('\\', $name, 2);
            $alias = strtolower($parts[0]);
            $candidates = isset($imports[$alias])
                ? [$imports[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '')]
                : [($namespace === '' ? '' : $namespace . '\\') . $name, $name];
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) || interface_exists($candidate)) {
                return is_subclass_of($candidate, RestAnnotationInterface::class)
                    ? (new ReflectionClass($candidate))->getName()
                    : null;
            }
        }

        return null;
    }
}
