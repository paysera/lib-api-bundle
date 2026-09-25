<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
class DocblockAnnotationFinder
{
    private const NAME = '[a-z_\\\\][a-z0-9_:\\\\]*[a-z_][a-z0-9_]*|[a-z_]';

    private const STRING = '"(?:""|[^"])*+"';

    private const TOKEN_PATTERN = '/' . self::STRING . '|(?<![^\s*"])@((?:' . self::NAME . ')(?:\\\\[\s*]*+(?:'
        . self::NAME . '))*+)(-(?![0-9]))?/';

    private const ARGUMENTS_PATTERN = '/\G[\s*]*+(\((?:' . self::STRING . '|[^()"]++|"|(?1))*+\))/';

    /**
     * @var array<string, array<string, string>>
     */
    private $importsByClass = [];

    /**
     * @var bool
     */
    private $bundleAnnotationsLoaded = false;

    /**
     * @return string[]
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
        $pattern = self::TOKEN_PATTERN . (preg_match('//u', $text) === 1 ? 'iu' : 'i');
        while (preg_match($pattern, $text, $token, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $offset = $token[0][1] + strlen($token[0][0]);
            $isString = !isset($token[1]);
            $isFollowedByDash = isset($token[2]);
            if ($isString || $isFollowedByDash) {
                continue;
            }

            $name = (string)preg_replace('/[\s*]++/u', '', $token[1][0]);
            $importedName = $this->resolveImportedName($name, $imports);
            $candidates = $importedName !== null ? [$importedName] : [$namespace . '\\' . $name, $name];
            $className = $this->findClass($candidates);
            if ($className === null) {
                continue;
            }
            if (is_subclass_of($className, RestAnnotationInterface::class)) {
                $found[] = $className;
            }
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
     */
    private function resolveImportedName(string $name, array $imports): ?string
    {
        if ($name[0] === '\\') {
            return ltrim($name, '\\');
        }

        $parts = explode('\\', $name, 2);
        $alias = strtolower($parts[0]);
        if (!isset($imports[$alias])) {
            return null;
        }

        return $imports[$alias] . (isset($parts[1]) ? '\\' . $parts[1] : '');
    }

    /**
     * @param string[] $candidates
     */
    private function findClass(array $candidates): ?string
    {
        $this->loadBundleAnnotations();
        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return (new ReflectionClass($candidate))->getName();
            }
        }

        return null;
    }

    private function loadBundleAnnotations(): void
    {
        if ($this->bundleAnnotationsLoaded) {
            return;
        }
        $this->bundleAnnotationsLoaded = true;
        $namespace = (new ReflectionClass(RestAnnotationInterface::class))->getNamespaceName();
        foreach (scandir(dirname(__DIR__, 2) . '/Annotation') ?: [] as $file) {
            if (substr($file, -4) === '.php') {
                class_exists($namespace . '\\' . substr($file, 0, -4));
            }
        }
    }

    private function isAnnotationClass(string $className): bool
    {
        return strpos((string)(new ReflectionClass($className))->getDocComment(), '@Annotation') !== false;
    }

    /**
     * @return array<string, string>
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
            || (PHP_VERSION_ID >= 80000 && in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true))
        );
    }
}
