<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Annotations\PhpParser;
use Doctrine\Common\Annotations\Reader;
use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * @internal
 */
class DocblockAnnotationReader
{
    private const ANNOTATION_NAMESPACE = 'paysera\bundle\apibundle\annotation';

    /**
     * @var AnnotationReader|null
     */
    private $annotationReader;

    /**
     * @var PhpParser|null
     */
    private $phpParser;

    /**
     * @var DocParser|null
     */
    private $docParser;

    /**
     * @var array<string, array<string, string>>
     */
    private $imports;

    /**
     * @var array<string, bool>
     */
    private $classesUsingAnnotations;

    /**
     * @var array<string, object[]>
     */
    private $classAnnotations;

    public function __construct()
    {
        $this->imports = [];
        $this->classesUsingAnnotations = [];
        $this->classAnnotations = [];
    }

    /**
     * @return object[]
     */
    public function readAnnotations(ReflectionClass $class, ReflectionMethod $method, ?Reader $reader): array
    {
        if ($reader !== null) {
            return array_merge($this->getClassAnnotations($reader, $class), $reader->getMethodAnnotations($method));
        }

        $readClass = $this->classUsesAnnotations($class);
        if (!$readClass && !$this->methodUsesAnnotations($method)) {
            return [];
        }

        $this->annotationReader = $this->annotationReader ?? new AnnotationReader();

        return array_merge(
            $readClass ? $this->getClassAnnotations($this->annotationReader, $class) : [],
            $this->annotationReader->getMethodAnnotations($method)
        );
    }

    /**
     * @return object[]
     */
    private function getClassAnnotations(Reader $reader, ReflectionClass $class): array
    {
        $className = $class->getName();
        if (!isset($this->classAnnotations[$className])) {
            $this->classAnnotations[$className] = $reader->getClassAnnotations($class);
        }

        return $this->classAnnotations[$className];
    }

    private function classUsesAnnotations(ReflectionClass $class): bool
    {
        $className = $class->getName();
        if (!isset($this->classesUsingAnnotations[$className])) {
            $imports = $this->getImports($class);
            $this->classesUsingAnnotations[$className] = $this->importsAnnotationNamespace($imports)
                || $this->mayHaveRestAnnotations($class->getDocComment(), $imports, $class->getNamespaceName());
        }

        return $this->classesUsingAnnotations[$className];
    }

    private function methodUsesAnnotations(ReflectionMethod $method): bool
    {
        $imports = [];
        foreach ($this->getActionScopes($method) as $scope) {
            $scopeImports = $this->getImports($scope);
            if ($this->importsAnnotationNamespace($scopeImports)) {
                return true;
            }

            $imports = array_merge($imports, $scopeImports);
        }

        return $this->mayHaveRestAnnotations(
            $method->getDocComment(),
            $imports,
            $method->getDeclaringClass()->getNamespaceName()
        );
    }

    /**
     * @return ReflectionClass[]
     */
    private function getActionScopes(ReflectionMethod $method): array
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

    /**
     * @return array<string, string>
     */
    private function getImports(ReflectionClass $scope): array
    {
        $name = $scope->getName();
        if (!isset($this->imports[$name])) {
            $this->phpParser = $this->phpParser ?? new PhpParser();
            $this->imports[$name] = $this->phpParser->parseUseStatements($scope);
        }

        return $this->imports[$name];
    }

    /**
     * @param array<string, string> $imports
     */
    private function importsAnnotationNamespace(array $imports): bool
    {
        foreach ($imports as $import) {
            $normalizedImport = strtolower(ltrim($import, '\\')) . '\\';
            if (
                strpos($normalizedImport, self::ANNOTATION_NAMESPACE . '\\') === 0
                || strpos(self::ANNOTATION_NAMESPACE . '\\', $normalizedImport) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string|false $docComment
     * @param array<string, string> $imports
     */
    private function mayHaveRestAnnotations($docComment, array $imports, string $namespace): bool
    {
        if ($docComment === false) {
            return false;
        }

        if ($this->docParser === null) {
            $this->docParser = new DocParser();
            $this->docParser->setIgnoreNotImportedAnnotations(true);
        }
        $this->docParser->setImports($imports + ['__NAMESPACE__' => $namespace]);
        try {
            $annotations = $this->docParser->parse($docComment);
        } catch (Throwable $exception) {
            return true;
        }

        foreach ($annotations as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                return true;
            }
        }

        return false;
    }
}
