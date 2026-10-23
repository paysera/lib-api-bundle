<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\BodyContentType;
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute;
use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
class DocblockAnnotationGuard
{
    private const ANNOTATION_NAMESPACE = 'paysera\bundle\apibundle\annotation';
    private const ANNOTATIONS = [
        Body::class,
        BodyContentType::class,
        PathAttribute::class,
        Query::class,
        RequiredPermissions::class,
        ResponseNormalization::class,
        Validation::class,
    ];
    private const NOT_READ = 'docblock annotations of the bundle are not read here, because Symfony passes no'
        . ' annotation reader to the route loader; use the attributes of the bundle instead';
    private const TAG_FOUND = 'has the docblock tag @';
    private const OTHER_LIBRARY = ' (for an annotation of another library named like one of the bundle\'s, import its'
        . ' class under another alias)';
    private const SPLIT_NAME_PATTERN = '/\\\\[\s*]+(?=[A-Za-z_])/';

    /**
     * @var array<string, string|null>
     */
    private $findings;

    /**
     * @var string
     */
    private $tagPattern;

    public function __construct()
    {
        $this->findings = [];
        $names = array_map(function (string $annotation): string {
            return substr($annotation, strrpos($annotation, '\\') + 1);
        }, self::ANNOTATIONS);
        $this->tagPattern = sprintf(
            '/(?<![A-Za-z0-9_\\\\])@(\\\\*(?:[A-Za-z_][A-Za-z0-9_]*\\\\)*(?:%s))(?![A-Za-z0-9_\\\\])/i',
            implode('|', $names)
        );
    }

    public function failOnDocblockAnnotations(ReflectionClass $class, ReflectionMethod $method): void
    {
        foreach ($this->getFiles($class, $method) as $file) {
            $finding = $this->findAnnotationUse($class, $method, $file);
            if ($finding !== null) {
                throw new ConfigurationException(sprintf(
                    '%s::%s(): %s %s, but %s%s',
                    $class->getName(),
                    $method->getName(),
                    $file,
                    $finding,
                    self::NOT_READ,
                    strpos($finding, self::TAG_FOUND) === 0 ? self::OTHER_LIBRARY : ''
                ));
            }
        }
    }

    /**
     * @return ReflectionClass[]
     */
    public function getActionScopes(ReflectionMethod $method): array
    {
        $declaringClass = $method->getDeclaringClass();
        $scopes = [$declaringClass];
        foreach ($this->getTraits($declaringClass) as $trait) {
            if ($trait->getFileName() === $method->getFileName()) {
                $scopes[] = $trait;
            }
        }

        return $scopes;
    }

    /**
     * @return array<string|null>
     */
    private function getFiles(ReflectionClass $class, ReflectionMethod $method): array
    {
        $declaringClass = $method->getDeclaringClass();
        $files = [$method->getFileName()];
        $scopes = array_merge(
            [$class, $declaringClass],
            $this->getTraits($class),
            $this->getTraits($declaringClass)
        );
        foreach ($scopes as $scope) {
            $files[] = $scope->getFileName();
        }

        return array_map(function ($file): ?string {
            return $file === false ? null : $file;
        }, array_unique($files));
    }

    /**
     * @return ReflectionClass[]
     */
    private function getTraits(ReflectionClass $class): array
    {
        $traits = [];
        foreach ($class->getTraits() as $trait) {
            $traits[] = $trait;
            $traits = array_merge($traits, $this->getTraits($trait));
        }

        return $traits;
    }

    private function findAnnotationUse(ReflectionClass $class, ReflectionMethod $method, ?string $file): ?string
    {
        if ($file !== null && array_key_exists($file, $this->findings)) {
            return $this->findings[$file];
        }

        $source = $file === null ? false : @file_get_contents($file);
        if ($source === false) {
            throw new ConfigurationException(sprintf(
                '%s::%s(): %s cannot be read, so its docblocks cannot be checked for annotations of the bundle; %s',
                $class->getName(),
                $method->getName(),
                $file === null ? 'the source of ' . $class->getName() : $file,
                self::NOT_READ
            ));
        }

        $this->findings[$file] = $this->findAnnotationUseInSource($source);

        return $this->findings[$file];
    }

    private function findAnnotationUseInSource(string $source): ?string
    {
        $tokens = token_get_all($source);
        foreach ($this->parseImports($tokens) as $import) {
            $normalizedImport = strtolower(ltrim($import, '\\'));
            if (
                $normalizedImport === self::ANNOTATION_NAMESPACE
                || strpos($normalizedImport, self::ANNOTATION_NAMESPACE . '\\') === 0
            ) {
                return 'imports ' . $import;
            }
        }

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $tag = $this->findTag($token[1]);
                if ($tag !== null) {
                    return $tag;
                }
            }
        }

        return null;
    }

    private function findTag(string $docComment): ?string
    {
        $joined = $this->joinNamesSplitAcrossLines($docComment);
        $matched = $joined === null ? false : preg_match($this->tagPattern, $joined, $match);
        if ($matched === false) {
            return 'has a docblock that cannot be checked (preg error ' . preg_last_error() . ')';
        }

        return $matched === 1 ? self::TAG_FOUND . $match[1] : null;
    }

    private function joinNamesSplitAcrossLines(string $docComment): ?string
    {
        $joined = preg_replace(self::SPLIT_NAME_PATTERN . 'u', '\\\\', $docComment);
        if ($joined === null && preg_last_error() === PREG_BAD_UTF8_ERROR) {
            $joined = preg_replace(self::SPLIT_NAME_PATTERN, '\\\\', $docComment);
        }

        return $joined;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @return string[]
     */
    private function parseImports(array $tokens): array
    {
        $imports = [];
        $count = count($tokens);
        for ($index = 0; $index < $count; $index++) {
            if (!is_array($tokens[$index]) || $tokens[$index][0] !== T_USE) {
                continue;
            }

            $statement = '';
            while (++$index < $count) {
                $token = $tokens[$index];
                if (is_array($token)) {
                    $isSpace = in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
                    $statement .= $isSpace ? ' ' : $token[1];
                    continue;
                }
                if ($token === ';') {
                    break;
                }
                if ($this->opensClosureOrTraitBlock($token, $statement)) {
                    $statement = '';
                    break;
                }
                $statement .= $token;
            }

            $imports = array_merge($imports, $this->expandImports($statement));
        }

        return $imports;
    }

    private function opensClosureOrTraitBlock(string $token, string $statement): bool
    {
        return $token === '(' || ($token === '{' && substr(rtrim($statement), -1) !== '\\');
    }

    /**
     * @return string[]
     */
    private function expandImports(string $statement): array
    {
        $normalized = preg_replace('/\s+/', ' ', $statement);
        if ($normalized === null) {
            return [$statement];
        }

        $normalized = trim($normalized);
        if ($normalized === '') {
            return [];
        }

        $prefix = '';
        $names = $normalized;
        $openingBrace = strpos($normalized, '{');
        if ($openingBrace !== false) {
            $closingBrace = strrpos($normalized, '}');
            $prefix = trim(substr($normalized, 0, $openingBrace));
            $names = substr(
                $normalized,
                $openingBrace + 1,
                ($closingBrace === false ? strlen($normalized) : $closingBrace) - $openingBrace - 1
            );
        }

        $imports = [];
        foreach (explode(',', $names) as $name) {
            $withoutAlias = preg_replace('/\s+as\s+\S+$/i', '', trim($name));
            $imports[] = str_replace(' ', '', $prefix . ($withoutAlias === null ? $name : $withoutAlias));
        }

        return $imports;
    }
}
