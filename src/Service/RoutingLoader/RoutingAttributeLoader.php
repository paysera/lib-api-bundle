<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Annotations\PhpParser;
use Paysera\Bundle\ApiBundle\Annotation\Body;
use Paysera\Bundle\ApiBundle\Annotation\BodyContentType;
use Paysera\Bundle\ApiBundle\Annotation\PathAttribute;
use Paysera\Bundle\ApiBundle\Annotation\Query;
use Paysera\Bundle\ApiBundle\Annotation\RequiredPermissions;
use Paysera\Bundle\ApiBundle\Annotation\ResponseNormalization;
use Paysera\Bundle\ApiBundle\Annotation\RestAnnotationInterface;
use Paysera\Bundle\ApiBundle\Annotation\Validation;
use Paysera\Bundle\ApiBundle\Attribute\RestAttributeInterface;
use Paysera\Bundle\ApiBundle\Exception\ConfigurationException;
use Paysera\Bundle\ApiBundle\Service\RestRequestHelper;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
class RoutingAttributeLoader extends AttributeRouteControllerLoader
{
    private const ANNOTATION_NAMESPACE = 'Paysera\\Bundle\\ApiBundle\\Annotation\\';
    private const ANNOTATIONS = [
        Body::class,
        BodyContentType::class,
        PathAttribute::class,
        Query::class,
        RequiredPermissions::class,
        ResponseNormalization::class,
        Validation::class,
    ];

    /**
     * @var RestRequestHelper
     */
    private $restRequestHelper;

    /**
     * @var RestRequestAnnotationOptionsBuilder
     */
    private $annotationOptionsBuilder;

    /**
     * @var RestRequestAttributeOptionsBuilder
     */
    private $attributeOptionsBuilder;

    /**
     * @var bool
     */
    private $docblockAnnotationsEnabled = true;

    /**
     * @var DocParser|null
     */
    private $docParser;

    /**
     * @var array<string, array<string, string>>
     */
    private $useStatements = [];

    /**
     * @var array<string, object[]>
     */
    private $docblockAnnotations = [];

    public function setRequestHelper(RestRequestHelper $restRequestHelper)
    {
        $this->restRequestHelper = $restRequestHelper;
    }

    public function setRestRequestAnnotationOptionsBuilder(RestRequestAnnotationOptionsBuilder $annotationOptionsBuilder
    ) {
        $this->annotationOptionsBuilder = $annotationOptionsBuilder;
    }

    public function setRestRequestAttributeOptionsBuilder(RestRequestAttributeOptionsBuilder $attributeOptionsBuilder)
    {
        $this->attributeOptionsBuilder = $attributeOptionsBuilder;
    }

    public function setDocblockAnnotationsEnabled(bool $docblockAnnotationsEnabled)
    {
        $this->docblockAnnotationsEnabled = $docblockAnnotationsEnabled;
    }

    protected function configureRoute(
        Route $route,
        ReflectionClass $class,
        ReflectionMethod $method,
        object $annot
    ): void {
        parent::configureRoute($route, $class, $method, $annot);

        if ($this->docblockAnnotationsEnabled) {
            $this->loadAnnotations($route, $class, $method);
        }
        $this->loadAttributes($route, $class, $method);
    }

    private function loadAnnotations(Route $route, ReflectionClass $class, ReflectionMethod $method): void
    {
        if (isset($this->reader)) {
            $classAnnotations = $this->reader->getClassAnnotations($class);
            $methodAnnotations = $this->reader->getMethodAnnotations($method);
        } else {
            $this->checkDocblocksAreKept();
            $classAnnotations = $this->parseDocblock(
                'class ' . $class->getName(),
                $class->getDocComment(),
                Target::TARGET_CLASS,
                $class
            );
            $methodAnnotations = $this->parseDocblock(
                'method ' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()',
                $method->getDocComment(),
                Target::TARGET_METHOD,
                $method->getDeclaringClass(),
                $method
            );
        }

        $annotations = [];
        foreach (array_merge($classAnnotations, $methodAnnotations) as $annotation) {
            if ($annotation instanceof RestAnnotationInterface) {
                $annotations[] = $annotation;
            }
        }

        if ($annotations === []) {
            return;
        }

        $this->restRequestHelper->setOptionsForRoute(
            $route,
            $this->annotationOptionsBuilder->buildOptions($annotations, $method)
        );
    }

    private function checkDocblocksAreKept(): void
    {
        $opcacheEnabled = extension_loaded('Zend OPcache')
            && $this->isIniEnabled('opcache.enable')
            && (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true) || $this->isIniEnabled('opcache.enable_cli'));

        if ($opcacheEnabled && !$this->isIniEnabled('opcache.save_comments')) {
            throw new ConfigurationException(
                'OPcache strips docblocks (opcache.save_comments=0), so the docblock annotations of the routes cannot'
                . ' be read without an annotation reader. Enable opcache.save_comments, as Doctrine\'s annotation'
                . ' reader requires'
            );
        }
    }

    private function isIniEnabled(string $name): bool
    {
        return filter_var(ini_get($name), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param string|false $docComment
     * @return object[]
     */
    private function parseDocblock(
        string $context,
        $docComment,
        int $target,
        ReflectionClass $class,
        ?ReflectionMethod $method = null
    ): array {
        if (!isset($this->docblockAnnotations[$context])) {
            $this->docblockAnnotations[$context] = $docComment === false || strpos($docComment, '@') === false
                ? []
                : $this->parseTags($context, $docComment, $target, $class, $method);
        }

        return $this->docblockAnnotations[$context];
    }

    /**
     * @return object[]
     */
    private function parseTags(
        string $context,
        string $docComment,
        int $target,
        ReflectionClass $class,
        ?ReflectionMethod $method
    ): array {
        if ($this->docParser === null) {
            $this->docParser = new DocParser();
            $this->docParser->setIgnoreNotImportedAnnotations(true);
        }
        $this->docParser->setTarget($target);

        $useStatements = $this->getImports($class, $method);
        $imports = $this->getResolvableImports($useStatements, $docComment, $class->getNamespaceName());
        $this->docParser->setImports($imports);
        $annotations = $this->docParser->parse($docComment, $context);

        $unresolvedImports = $this->getUnresolvedAnnotationImports($useStatements, $class);
        if ($unresolvedImports !== []) {
            $this->docParser->setImports(array_merge($imports, $unresolvedImports));
            $this->refuseUnresolvedAnnotations($context, $annotations, $this->docParser->parse($docComment, $context));
        }

        return $annotations;
    }

    /**
     * @param array<string, string> $useStatements
     * @return array<string, string>
     */
    private function getResolvableImports(array $useStatements, string $docComment, string $namespace): array
    {
        $imports = [];
        foreach ($useStatements as $alias => $name) {
            if (
                $this->isInAnnotationNamespace($name)
                || (
                    stripos($docComment, $alias) !== false
                    && (class_exists($name) || interface_exists($name))
                    && !$this->isAnnotationClass($name)
                )
            ) {
                $imports[$alias] = $name;
            }
        }
        if ($this->isInAnnotationNamespace($namespace)) {
            $imports['__NAMESPACE__'] = $namespace;
        }

        return $imports;
    }

    /**
     * @param array<string, string> $useStatements
     * @return array<string, string>
     */
    private function getUnresolvedAnnotationImports(array $useStatements, ReflectionClass $class): array
    {
        $namespace = $class->getNamespaceName();
        $imports = [];
        foreach (self::ANNOTATIONS as $annotation) {
            $name = substr($annotation, strlen(self::ANNOTATION_NAMESPACE));
            $alias = strtolower($name);
            $resolved = isset($useStatements[$alias])
                ? $this->isAnnotationClass($useStatements[$alias])
                : $this->isAnnotationClass($namespace . '\\' . $name) || $this->isAnnotationClass($name);
            if (!$resolved) {
                $imports[$alias] = $annotation;
            }
        }

        $classUseStatements = $this->getUseStatements($class);
        foreach ($this->getTraits($class) as $trait) {
            $classUseStatements = array_merge($classUseStatements, $this->getUseStatements($trait));
        }
        foreach ($classUseStatements as $alias => $name) {
            if (!isset($useStatements[$alias]) && $this->isInAnnotationNamespace($name)) {
                $imports[$alias] = $name;
            }
        }

        return $imports;
    }

    private function isAnnotationClass(string $name): bool
    {
        return class_exists($name)
            && strpos((string)(new ReflectionClass($name))->getDocComment(), '@Annotation') !== false;
    }

    /**
     * @param object[] $annotations
     * @param object[] $annotationsWithUnresolved
     */
    private function refuseUnresolvedAnnotations(
        string $context,
        array $annotations,
        array $annotationsWithUnresolved
    ): void {
        $counts = array_count_values(array_map('get_class', $annotations));
        foreach ($annotationsWithUnresolved as $annotation) {
            $class = get_class($annotation);
            if (($counts[$class] ?? 0) === 0) {
                throw new ConfigurationException(sprintf(
                    'The annotation "@%s" in %s does not resolve to a class, so it would be ignored: import %s',
                    substr($class, strlen(self::ANNOTATION_NAMESPACE)),
                    $context,
                    $class
                ));
            }
            $counts[$class]--;
        }
    }

    /**
     * @return array<string, string>
     */
    private function getImports(ReflectionClass $class, ?ReflectionMethod $method): array
    {
        $imports = $this->getUseStatements($class);
        if ($method === null) {
            return $imports;
        }

        foreach ($this->getTraits($class) as $trait) {
            if (
                $trait->getFileName() === $method->getFileName()
                && $trait->getStartLine() <= $method->getStartLine()
                && $method->getEndLine() <= $trait->getEndLine()
            ) {
                $imports = array_merge($imports, $this->getUseStatements($trait));
            }
        }

        return $imports;
    }

    /**
     * @return ReflectionClass[]
     */
    private function getTraits(ReflectionClass $class): array
    {
        $traits = [];
        foreach ($class->getTraits() as $trait) {
            $traits = array_merge($traits, [$trait], $this->getTraits($trait));
        }

        return $traits;
    }

    /**
     * @return array<string, string>
     */
    private function getUseStatements(ReflectionClass $class): array
    {
        $name = $class->getName();
        if (!isset($this->useStatements[$name])) {
            $this->useStatements[$name] = (new PhpParser())->parseUseStatements($class);
        }

        return $this->useStatements[$name];
    }

    private function isInAnnotationNamespace(string $name): bool
    {
        $namespace = strtolower(ltrim($name, '\\') . '\\');
        $annotationNamespace = strtolower(self::ANNOTATION_NAMESPACE);

        return strpos($namespace, $annotationNamespace) === 0 || strpos($annotationNamespace, $namespace) === 0;
    }

    private function loadAttributes(Route $route, ReflectionClass $class, ReflectionMethod $method): void
    {
        $attributes = array_merge($class->getAttributes(), $method->getAttributes());

        $restAttributes = [];
        foreach ($attributes as $attribute) {
            if (is_subclass_of($attribute->getName(), RestAttributeInterface::class)) {
                /** @var RestAttributeInterface $instance */
                $instance = $attribute->newInstance();
                $restAttributes[] = $instance;
            }
        }

        if ($restAttributes === []) {
            return;
        }

        $this->restRequestHelper->setOptionsForRoute(
            $route,
            $this->attributeOptionsBuilder->buildOptions($restAttributes, $method)
        );
    }
}
