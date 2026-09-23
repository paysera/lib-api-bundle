<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * Finds this bundle's annotations in the docblocks of a controller method and its class without an annotation reader:
 * each docblock tag is resolved through the `use` imports of the file that declares it.
 *
 * @internal
 */
class DocblockAnnotationFinder
{
    /**
     * @return string[] class names of the bundle's annotations the docblocks use, in order, each once
     */
    public function findBundleAnnotations(ReflectionClass $class, ReflectionMethod $method): array
    {
        $found = array_merge(
            $this->findInDocblock($class->getDocComment(), $class),
            $this->findInDocblock($method->getDocComment(), $method->getDeclaringClass())
        );

        return array_values(array_unique($found));
    }

    /**
     * @param string|false $docComment
     * @return string[]
     */
    private function findInDocblock($docComment, ReflectionClass $declaringClass): array
    {
        if ($docComment === false || strpos($docComment, '@') === false) {
            return [];
        }

        preg_match_all('/@(\\\\?[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*)/', $docComment, $matches);
        $imports = $this->readImports($declaringClass);

        $found = [];
        foreach ($matches[1] as $name) {
            $className = $this->resolveClassName($name, $imports, $declaringClass->getNamespaceName());
            if (is_subclass_of($className, RestAnnotationInterface::class)) {
                $found[] = ltrim((new ReflectionClass($className))->getName(), '\\');
            }
        }

        return $found;
    }

    /**
     * @return array<string, string> imported class or namespace name by its lower-case alias
     */
    private function readImports(ReflectionClass $class): array
    {
        // only internal classes have no file, and they carry no docblocks, so this method never sees one
        $fileName = $class->getFileName();
        preg_match_all(
            '/^\s*use\s+(\\\\?[A-Za-z_][A-Za-z0-9_\\\\]*)(?:\s+as\s+([A-Za-z_][A-Za-z0-9_]*))?\s*;/mi',
            $fileName === false ? '' : (string)file_get_contents($fileName),
            $matches,
            PREG_SET_ORDER
        );

        $imports = [];
        foreach ($matches as $match) {
            $importedName = ltrim($match[1], '\\');
            $parts = explode('\\', $importedName);
            $alias = isset($match[2]) && $match[2] !== '' ? $match[2] : end($parts);
            $imports[strtolower($alias)] = $importedName;
        }

        return $imports;
    }

    /**
     * @param array<string, string> $imports
     */
    private function resolveClassName(string $name, array $imports, string $namespace): string
    {
        if ($name[0] === '\\') {
            return ltrim($name, '\\');
        }

        $parts = explode('\\', $name, 2);
        $alias = strtolower($parts[0]);
        if (isset($imports[$alias])) {
            return $imports[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
        }

        return $namespace === '' ? $name : $namespace . '\\' . $name;
    }
}
