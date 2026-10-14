<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\AnnotationException;
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
        $unresolvedImports = array_filter(
            $this->getUnresolvedAnnotationImports($useStatements, $class),
            static function (string $alias) use ($docComment): bool {
                return preg_match('/@' . preg_quote($alias, '/') . '(?!\w)/i', $docComment) === 1;
            },
            ARRAY_FILTER_USE_KEY
        );
        $namespace = $class->getNamespaceName();
        $this->refuseDoubledBackslashes($context, $docComment, $useStatements, $namespace);
        $this->docParser->setImports($imports);
        try {
            $annotations = $this->docParser->parse($docComment, $context);
        } catch (AnnotationException $exception) {
            $this->refuseUnresolvedAliasedAnnotations($context, $docComment, $useStatements, $imports, $namespace);
            $this->refuseUnresolvedNestedAnnotation($context, $docComment, $imports, $unresolvedImports);
            throw $exception;
        }

        if ($unresolvedImports !== []) {
            $this->docParser->setImports(array_merge($imports, $unresolvedImports));
            $this->refuseUnresolvedAnnotations(
                $context,
                $useStatements,
                $annotations,
                $this->docParser->parse($docComment, $context)
            );
        }
        $this->refuseUnresolvedAliasedAnnotations($context, $docComment, $useStatements, $imports, $namespace);

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
        $tags = $this->getTags($docComment);
        foreach ($references as [, $name, $path]) {
            $alias = strtolower($name);
            if (isset($imports[$alias])) {
                continue;
            }
            $prefix = $useStatements[$alias] ?? ltrim($namespace . '\\' . $name, '\\');
            $reference = $prefix . $path;
            $writtenAsTag = $path === ''
                ? $this->isAnnotationClass($reference) && $this->isWrittenAsTag($tags, $name, false)
                : $this->isWrittenAsTag($tags, $name, true);
            if ((class_exists($reference) || interface_exists($reference)) && !$writtenAsTag) {
                $imports[$alias] = $prefix;
            }
        }

        foreach ($tags as [$name, $path]) {
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
     * @param array<array{string, string, string}> $tags
     */
    private function isWrittenAsTag(array $tags, string $name, bool $withPath): bool
    {
        foreach ($tags as [$tagName, $tagPath]) {
            if (strcasecmp($tagName, $name) === 0 && ($tagPath !== '') === $withPath) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<array{string, string, string}> the tags that are not fully qualified, as readTags() returns them
     */
    private function getTags(string $docComment): array
    {
        $tags = [];
        foreach ($this->readTags($docComment) as [$name, $path, $written, $fullyQualified]) {
            if (!$fullyQualified) {
                $tags[] = [$name, $path, $written];
            }
        }

        return $tags;
    }

    /**
     * Tags as Doctrine's lexer reads them: from the first tag on, not inside a double-quoted string, an @ after
     * whitespace, *, or a value's =, comma or {, and a name joined across a backslash and a line break.
     *
     * @return array<array{string, string, string, bool}> for each tag: its first name, the rest of its name with single
     *     backslashes, that rest as written, and whether it is fully qualified
     */
    private function readTags(string $docComment): array
    {
        if (preg_match('/(?<![^ \t*])@/', $docComment, $first, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }
        $withoutStrings = (string)preg_replace('/"(?:""|[^"])*+"/', '""', substr($docComment, $first[0][1]));
        preg_match_all(
            '/(?<![^\s*=,{])@(\\\\*)([a-z_]\w*)((?:\\\\+[\s*]*\w+)*)/i',
            $withoutStrings,
            $tags,
            PREG_SET_ORDER
        );

        return array_map(static function (array $tag): array {
            $written = (string)preg_replace('/[\s*]+/', '', $tag[3]);

            return [$tag[2], (string)preg_replace('/\\\\+/', '\\\\', $written), $written, $tag[1] !== ''];
        }, $tags);
    }

    /**
     * @param array<string, string> $useStatements
     */
    private function refuseDoubledBackslashes(
        string $context,
        string $docComment,
        array $useStatements,
        string $namespace
    ): void {
        foreach ($this->readTags($docComment) as [$name, $path, $written, $fullyQualified]) {
            if ($written === $path) {
                continue;
            }
            $candidates = $fullyQualified
                ? [$name . $path]
                : $this->getTagCandidates($name, $path, $useStatements, $namespace);
            $resolved = array_values(array_filter($candidates, 'class_exists'));
            if (
                $this->isBundleTag($path, $candidates)
                || ($resolved !== [] && $this->isRestAnnotationClass($resolved[0]))
            ) {
                throw new ConfigurationException(sprintf(
                    'The annotation "@%s" in %s is written with a doubled backslash: write one',
                    ($fullyQualified ? '\\' : '') . $name . $written,
                    $context
                ));
            }
        }
    }

    /**
     * @param array<string, string> $useStatements
     * @return string[] the class names Doctrine's reader tries for the tag, in its order
     */
    private function getTagCandidates(string $name, string $path, array $useStatements, string $namespace): array
    {
        $alias = strtolower($name);

        return isset($useStatements[$alias])
            ? [$useStatements[$alias] . $path]
            : [ltrim($namespace . '\\' . $name . $path, '\\'), $name . $path];
    }

    /**
     * @param string[] $candidates
     */
    private function isBundleTag(string $path, array $candidates): bool
    {
        $shortNames = array_map(static function (string $annotation): string {
            return strtolower(substr($annotation, strlen(self::ANNOTATION_NAMESPACE)));
        }, self::ANNOTATIONS);
        if (in_array(strtolower(substr((string)strrchr($path, '\\'), 1)), $shortNames, true)) {
            return true;
        }
        foreach ($candidates as $candidate) {
            if (stripos(ltrim($candidate, '\\'), self::ANNOTATION_NAMESPACE) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $imports
     * @param array<string, string> $unresolvedImports
     */
    private function refuseUnresolvedNestedAnnotation(
        string $context,
        string $docComment,
        array $imports,
        array $unresolvedImports
    ): void {
        foreach ($unresolvedImports as $alias => $annotation) {
            if (!class_exists($annotation)) {
                continue;
            }
            $this->docParser->setImports(array_merge($imports, [$alias => $annotation]));
            try {
                $this->docParser->parse($docComment, $context);
            } catch (AnnotationException $exception) {
                continue;
            }
            throw new ConfigurationException(sprintf(
                'The annotation "@%s" in %s does not resolve to a class, so it would be ignored: import %s',
                substr($annotation, strlen(self::ANNOTATION_NAMESPACE)),
                $context,
                $annotation
            ));
        }
    }

    /**
     * @param array<string, string> $useStatements
     * @param array<string, string> $imports
     */
    private function refuseUnresolvedAliasedAnnotations(
        string $context,
        string $docComment,
        array $useStatements,
        array $imports,
        string $namespace
    ): void {
        $tags = $this->getTags($docComment);
        foreach ($tags as [$name, $path]) {
            $candidates = $this->getTagCandidates($name, $path, $useStatements, $namespace);
            $resolved = array_values(array_filter($candidates, 'class_exists'));
            if ($resolved !== [] && $path !== '' && $this->isRestAnnotationClass($resolved[0])) {
                $declared = (new ReflectionClass($resolved[0]))->getName();
                if ($declared !== ltrim($resolved[0], '\\')) {
                    throw $this->createLetterCaseException($context, $name . $path, $declared);
                }
                $this->refuseSharedFirstName($context, $tags, $name, $path, $imports, $resolved[0]);
            }
            if ($resolved === [] && $this->isBundleTag($path, $candidates)) {
                $annotation = $this->findAnnotationInAnotherLetterCase($candidates);
                if ($annotation !== null) {
                    throw $this->createLetterCaseException($context, $name . $path, $annotation);
                }
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
     * @param string[] $candidates
     */
    private function findAnnotationInAnotherLetterCase(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            foreach (self::ANNOTATIONS as $annotation) {
                if (strcasecmp(ltrim($candidate, '\\'), $annotation) === 0) {
                    return $annotation;
                }
            }
        }

        return null;
    }

    private function createLetterCaseException(string $context, string $tag, string $class): ConfigurationException
    {
        return new ConfigurationException(sprintf(
            'The annotation "@%s" in %s is written in another letter case: write it as %s',
            $tag,
            $context,
            $class
        ));
    }

    /**
     * @param array<array{string, string, string}> $tags
     * @param array<string, string> $imports
     */
    private function refuseSharedFirstName(
        string $context,
        array $tags,
        string $name,
        string $path,
        array $imports,
        string $class
    ): void {
        $alias = strtolower($name);
        if (!isset($imports[$alias]) || strcasecmp($imports[$alias] . $path, $class) === 0) {
            return;
        }
        foreach ($tags as [$otherName, $otherPath]) {
            if (strtolower($otherName) === $alias && class_exists($imports[$alias] . $otherPath)) {
                throw new ConfigurationException(sprintf(
                    'The annotations "@%s" and "@%s" in %s start with the same name but resolve in different'
                    . ' namespaces: write one of them fully qualified',
                    $otherName . $otherPath,
                    $name . $path,
                    $context
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
                if (strcasecmp($import, $class) === 0) {
                    $message = 'The annotation "@%1$s" in %2$s is imported in another letter case (%3$s):'
                        . ' import %4$s so it autoloads';
                } elseif (class_exists($import) || interface_exists($import)) {
                    $message = 'The annotation "@%1$s" in %2$s resolves to %3$s, not to the annotation %4$s,'
                        . ' so it would be ignored';
                } else {
                    $message = 'The annotation "@%1$s" in %2$s does not resolve to a class,'
                        . ' so it would be ignored: import %4$s';
                }
                throw new ConfigurationException(sprintf($message, $name, $context, $import, $class));
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
