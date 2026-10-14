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
        $imports = $this->getResolvableImports($useStatements, $docComment, $class);
        $this->docParser->setImports($imports);
        $annotations = $this->docParser->parse($docComment, $context);

        $unresolvedImports = array_filter(
            $this->getUnresolvedAnnotationImports($useStatements, $class),
            static function (string $alias) use ($docComment): bool {
                return preg_match('/@' . preg_quote($alias, '/') . '(?!\w)/i', $docComment) === 1;
            },
            ARRAY_FILTER_USE_KEY
        );
        if ($unresolvedImports !== []) {
            $this->docParser->setImports(array_merge($imports, $unresolvedImports));
            $this->refuseUnresolvedAnnotations(
                $context,
                $useStatements,
                $annotations,
                $this->docParser->parse($docComment, $context)
            );
        }
        $this->refuseUnresolvedAliasedAnnotations($context, $docComment, $useStatements, $class->getNamespaceName());

        return $annotations;
    }

    /**
     * @param array<string, string> $useStatements
     * @return array<string, string>
     */
    private function getResolvableImports(array $useStatements, string $docComment, ReflectionClass $class): array
    {
        $namespace = $class->getNamespaceName();
        $imports = ['self' => $class->getName()];
        foreach ($useStatements as $alias => $name) {
            if (
                $this->isInAnnotationNamespace($name)
                || (stripos($docComment, $alias) !== false && $this->isNonAnnotationClass($name))
            ) {
                $imports[$alias] = $name;
            }
        }

        preg_match_all('/(?<![\w\\\\$])([a-z_]\w*)((?:\\\\\w+)*)::/i', $docComment, $references, PREG_SET_ORDER);
        foreach ($references as [, $name, $path]) {
            $alias = strtolower($name);
            if (isset($imports[$alias])) {
                continue;
            }
            $prefix = $useStatements[$alias] ?? ltrim($namespace . '\\' . $name, '\\');
            if (
                $this->isNonAnnotationClass($prefix . $path)
                && ($path === '' || stripos($docComment, '@' . $name . '\\') === false)
            ) {
                $imports[$alias] = $prefix;
            }
        }

        foreach ($this->getTags($docComment) as [$name, $path]) {
            $alias = strtolower($name);
            $prefix = $useStatements[$alias] ?? ltrim($namespace . '\\' . $name, '\\');
            if (!isset($imports[$alias]) && $this->isRestAnnotationClass($prefix . $path)) {
                $imports[$alias] = $prefix;
            }
        }

        if ($this->isInAnnotationNamespace($namespace)) {
            $imports['__NAMESPACE__'] = $namespace;
        }

        return $imports;
    }

    /**
     * @return array<array{string, string}> first name and the rest of each tag that is not fully qualified
     */
    private function getTags(string $docComment): array
    {
        preg_match_all('/(?<![\w\\\\@])@([a-z_]\w*)((?:\\\\\w+)*)/i', $docComment, $tags, PREG_SET_ORDER);

        return array_map(static function (array $tag): array {
            return [$tag[1], $tag[2]];
        }, $tags);
    }

    /**
     * @param array<string, string> $useStatements
     */
    private function refuseUnresolvedAliasedAnnotations(
        string $context,
        string $docComment,
        array $useStatements,
        string $namespace
    ): void {
        $shortNames = array_map(static function (string $annotation): string {
            return strtolower(substr($annotation, strlen(self::ANNOTATION_NAMESPACE)));
        }, self::ANNOTATIONS);
        foreach ($this->getTags($docComment) as [$name, $path]) {
            $lastName = strtolower(substr((string)strrchr($path, '\\'), 1));
            if (!in_array($lastName, $shortNames, true)) {
                continue;
            }
            $alias = strtolower($name);
            $candidates = isset($useStatements[$alias])
                ? [$useStatements[$alias] . $path]
                : [ltrim($namespace . '\\' . $name . $path, '\\'), $name . $path];
            if (count(array_filter($candidates, 'class_exists')) === 0) {
                throw new ConfigurationException(sprintf(
                    'The annotation "@%s" in %s does not resolve to a class, so it would be ignored: import it from %s',
                    $name . $path,
                    $context,
                    rtrim(self::ANNOTATION_NAMESPACE, '\\')
                ));
            }
        }
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

    private function isRestAnnotationClass(string $name): bool
    {
        return $this->isAnnotationClass($name) && is_subclass_of($name, RestAnnotationInterface::class);
    }

    private function isNonAnnotationClass(string $name): bool
    {
        return (class_exists($name) || interface_exists($name)) && !$this->isAnnotationClass($name);
    }

    private function isAnnotationClass(string $name): bool
    {
        return class_exists($name)
            && strpos((string)(new ReflectionClass($name))->getDocComment(), '@Annotation') !== false;
    }

    /**
     * @param array<string, string> $useStatements
     * @param object[] $annotations
     * @param object[] $annotationsWithUnresolved
     */
    private function refuseUnresolvedAnnotations(
        string $context,
        array $useStatements,
        array $annotations,
        array $annotationsWithUnresolved
    ): void {
        $counts = array_count_values(array_map('get_class', $annotations));
        foreach ($annotationsWithUnresolved as $annotation) {
            $class = get_class($annotation);
            if (($counts[$class] ?? 0) === 0) {
                $name = substr($class, strlen(self::ANNOTATION_NAMESPACE));
                $import = $useStatements[strtolower($name)] ?? '';
                throw new ConfigurationException(class_exists($import) || interface_exists($import)
                    ? sprintf(
                        'The annotation "@%s" in %s resolves to %s, not to the annotation %s, so it would be ignored',
                        $name,
                        $context,
                        $import,
                        $class
                    )
                    : sprintf(
                        'The annotation "@%s" in %s does not resolve to a class, so it would be ignored: import %s',
                        $name,
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
