# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]
### Added
- Support for Symfony 7.4
- Support for `psr/log` 3
- `docblock_annotations` option (default `true`), on Symfony 6.4 and later: with `false`, the bundle reads only its
  attributes and none of its docblock annotations, on `@Route` routes too, so the bundle itself no longer needs
  `opcache.save_comments`; Symfony's annotation reader still needs it where `framework.annotations` is enabled. Before
  Symfony 6.4 the option has no effect
- Where no annotation reader is available (on Symfony 7, on Symfony 6.4 with `framework.annotations` disabled, and with
  FrameworkBundle 6.4 and `symfony/routing` 7), the bundle reads its own docblock annotations on `#[Route]` methods and
  their classes with Doctrine's `DocParser`. A class docblock resolves names through the use statements of the class, a
  method docblock through those of the class declaring the method and of the trait that declares it, also when that
  trait is used by another trait or the method is aliased. An imported name is read as an annotation only when it points
  into `Paysera\Bundle\ApiBundle\Annotation`, so unknown tags and other libraries' imported annotations are ignored. An
  application's own annotation that implements `RestAnnotationInterface` is read as with Doctrine's annotation reader:
  imported, in the controller's namespace or relative to it, through a namespace alias, or fully qualified; one that
  resolves to no class, such as a forgotten import, is ignored like an unknown tag. Class names in annotation arguments
  (`Roles::ADMIN`, `UserDto::class`) resolve as with Doctrine's annotation reader: imported, through `self`, relative to
  the controller's namespace, or through a namespace alias, unless the same docblock also writes that name as a tag.

### Changed
- The Symfony components the bundle uses directly are required explicitly: `symfony/config`, `symfony/dependency-injection`,
  `symfony/http-foundation`, `symfony/http-kernel`, `symfony/property-access`, `symfony/routing` and `symfony/security-core`
- `Configuration::getConfigTreeBuilder()` declares its `TreeBuilder` return type. Breaking for subclasses that override it
  without the return type: add `: TreeBuilder` to the override
- The services are registered by `PayseraApiExtension` instead of the XML files in `Resources/config`, which are
  removed, so Symfony 7.4 no longer reports that the XML configuration format is deprecated. The service ids, classes,
  arguments, method calls, tags and visibility are unchanged
- `PayseraApiExtension` extends `Symfony\Component\DependencyInjection\Extension\Extension` instead of HttpKernel's
  `Extension`, which is internal since Symfony 7.1. Breaking for subclasses that call `addAnnotatedClassesToCompile()`
  or `getAnnotatedClassesToCompile()`, which only HttpKernel's `Extension` has: drop the calls
- `Configuration` is marked `@final`, so Symfony's `DebugClassLoader` reports classes that extend it
- Optional parameters are declared nullable explicitly (`?Type $parameter = null`), as PHP 8.4 expects
- Breaking where no annotation reader is available: the route load fails with a `ConfigurationException` when OPcache
  runs with `opcache.save_comments=0` for the current SAPI, as Doctrine's annotation reader requires, also in
  applications that use no docblock annotations; set `docblock_annotations: false` (Symfony 6.4 and later) where only
  attributes are used
- Breaking where no annotation reader is available: the route load fails with a `ConfigurationException` when a tag
  written as the short name of one of the bundle's annotations (`@RequiredPermissions`) does not resolve to that
  annotation, because its use statement is missing or names another class, and when a namespace alias of the bundle's
  annotations is used where only another trait of the controller imports it, and when a tag ending in one of those short
  names is written through a namespace alias and resolves to no class (`@Rest\RequiredPermissions` without `use … as
  Rest`, or with `Rest` imported for another namespace), when a tag resolves into `Paysera\Bundle\ApiBundle\Annotation`
  to a class that does not exist (an import of `…\Annotation\RequiredPermission`), when a nested annotation of the
  bundle does not resolve, when a tag of the bundle or an application's own annotation is written with a doubled
  backslash, has whitespace before a backslash inside its name (`@Rest \RequiredPermissions`) or has a colon in its
  name (`@RequiredPermissions:admin`), when a tag names `Paysera\Bundle\ApiBundle\Annotation` itself through an import
  (`@Rest RequiredPermissions`), when a tag of the bundle that is not fully qualified spells its class in another letter
  case, and when two tags with the same first name resolve in different namespaces. Tags are found with Doctrine's own
  lexer, as its parser finds them. The message names the
  class a use statement resolves the short name to, or says that the import differs in letter case; an import of one of
  the bundle's annotations in another letter case fails while the annotation's class is not loaded and applies once it
  is, as with Doctrine's annotation reader. `@IgnoreAnnotation` does not exempt the bundle's annotations. A fully
  qualified tag resolves as with Doctrine's annotation reader, so a class that does not exist, or an annotation on a
  target it does not allow, fails the route load with Doctrine's error. Any other tag that resolves to no class, such as
  a bare `@RequiredPermission` with no import, is ignored like an unknown tag
- `LocaleListener` reads the `Accept-Language` tags with the installed Symfony's `Request::getLanguages()` and compares
  them with the configured locales ignoring letter case, then sets the locale as configured: `DE` or `zh-hant-tw` now
  matches a configured `de` or `zh_Hant_TW`, where Symfony 3.4 to 7.0 matched only the exact case. From Symfony 7.1,
  `getLanguages()` drops a numeric region or a variant and returns an `x-` tag, or an `i-` tag with fewer than five
  letters, unchanged, so there `es-419` no longer matches a configured `es_419`, nor `i-ami` a configured `ami`
- CI runs the tests on PHP 8.4, on Symfony 7 with PHP 8.2 to 8.4, and with lowest dependencies

### Fixed
- On Symfony 7.1 and later, a request for `de-DE`, and from Symfony 7.3 also one for `de`, kept the default locale,
  because `LocaleListener` matched `de` against an internal `default` placeholder

### Security
- On Symfony 6.4 with `framework.annotations` disabled, and with FrameworkBundle 6.4 and `symfony/routing` 7, the
  bundle's docblock annotations, `@RequiredPermissions` among them, were ignored without an error; they are now applied
  unless `docblock_annotations` is `false`, and the cases listed under Changed fail the route load instead; any other
  tag that resolves to no class is still ignored like an unknown tag

## [1.8.2]
### Changed
- CI allows packages with security advisories, so Symfony 3.4 and 4.4 jobs can install dependencies with Composer 2.10

### Fixed
- Unsupported `sort` field on paginated endpoints now returns `400 invalid_parameters` instead of `500 internal_server_error`

## [1.8.1]
### Added
- `void` typehint to `PayseraApiBundle::build` method to fix the deprecation message
- PHP 8.3 to CI
### Fixed
- `Call to a member function getClassAnnotations() on null` error in `RoutingAttributeLoader` on Symfony 6

## [1.8.0]
### Added
- Support for PHP 8 attributes
- Support for `doctrine/annotations: ^2.0`

## [1.7.0]
### Added
- Support for Symfony 6.4

### Changed
- Fixed code style 
- Fixed tests

## [1.6.0]
### Added
- Allow library to work with various version of `paysera/lib-object-wrapper` from `0.1.0`

## [1.5.1]
### Changed
- Changed error handling signatures to work with new versions.

## [1.5.0]
### Changed
- `psr/log` bumped to `^2.0`

## [1.4.0]
### Added
- Support for Symfony 5.4

### Changed
- `symfony/framework-bundle` bumped to `^5.4`
- `symfony/security-bundle` bumped to `^5.4`
- `symfony/validator` bumped to `^5.4`
- `symfony/yaml` bumped to `^5.4`

## [1.3.1]
### Changed
- Updated the paysera/lib-normalization library.

## [1.3.0]
### Added
- support for PHP 8
- `doctrine/persistence` added

### Changed
- `paysera/lib-normalization-bundle` bumped to `^1.1.0`
- `paysera/lib-normalization` bumped to `^1.2.0`
- `paysera/lib-dependency-injection` bumped to `^1.3.0`
- `doctrine/doctrine-bundle` bumped to `^1.4|^2.0`
- `doctrine/orm` bumped to `^2.5.14|^2.6`

### Removed
- support for PHP 7.0
- `paysera/lib-php-cs-fixer-config`
- `doctrine/common`



## [1.2.0]
### Changed
- `paysera_api.listener.locale` listener priority changed.

## [1.1.0]
### Changed
- When using annotations, `RestRequestOptions` will now be available after `kernel.request` event instead of `kernel.controller` one. This allows to show proper REST errors when exceptions are raised in the firewall.

## [1.0.0]
### Changed
- `ErrorNormalizer` does not return keys with `null` values anymore.
This means that you'll get errors like this:

```json
{"error":"invalid_request","error_description":"Expected non-empty request body"}
```

instead of this:

```json
{"error":"invalid_request","error_description":"Expected non-empty request body","error_uri":null,"error_properties":null,"error_data":null,"errors":null}
```

## 0.2.1
### Changed
- `paysera/lib-pagination` bumped to `^1.0`

## 0.2.0
### Changed
- since `paysera/lib-normalization` version `1.0` null keys are not filtered.

[1.0.0]: https://github.com/paysera/lib-api-bundle/compare/v0.2.1...v1.0.0
