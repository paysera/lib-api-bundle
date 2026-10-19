# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]
### Added
- Support for Symfony 7.4
- Support for `psr/log` 3
- `docblock_annotations` option (default `false`) for setups where Symfony passes no annotation reader to the route
  loader (on Symfony 7, on Symfony 6.4 with `framework.annotations` disabled, and with FrameworkBundle 6.4 and
  `symfony/routing` 7): `true` reads the docblock annotations of `#[Route]` actions and of their classes with Doctrine's
  `AnnotationReader`, so the route load fails where that reader fails: on a docblock tag it cannot resolve, such as one
  that is neither imported nor ignored (`@note`; list it with `@IgnoreAnnotation` in the class docblock, where an
  annotation of the bundle that is not imported is then skipped too), and when OPcache is loaded with
  `opcache.save_comments=0`; `false` reads only attributes. Where Symfony passes its annotation reader, the bundle reads
  its docblock annotations with it, as before, whatever the option says. Before Symfony 6.4 the option has no effect

### Changed
- The Symfony components the bundle uses directly are required explicitly: `symfony/config`,
  `symfony/dependency-injection`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/property-access`,
  `symfony/routing` and `symfony/security-core`
- `doctrine/annotations` is required at 1.14.4 or 2.0.2 and later, the first releases whose `AnnotationReader` refuses
  to run with `opcache.save_comments=0` instead of reading the stripped docblocks as empty
- `Configuration::getConfigTreeBuilder()` declares its `TreeBuilder` return type. Breaking for subclasses that override
  it without the return type: add `: TreeBuilder` to the override
- The services are registered by `PayseraApiExtension` instead of the XML files in `Resources/config`, which are
  removed, so Symfony 7.4 no longer reports that the XML configuration format is deprecated. The service ids, classes,
  arguments, method calls, tags and visibility are unchanged
- `PayseraApiExtension` extends `Symfony\Component\DependencyInjection\Extension\Extension` instead of HttpKernel's
  `Extension`, which is internal since Symfony 7.1. Breaking for subclasses that call `addAnnotatedClassesToCompile()`
  or `getAnnotatedClassesToCompile()`, which only HttpKernel's `Extension` has: drop the calls
- `Configuration` is marked `@final`, so Symfony's `DebugClassLoader` reports classes that extend it
- Optional parameters are declared nullable explicitly (`?Type $parameter = null`), as PHP 8.4 expects
- CI runs the tests on PHP 8.4, on Symfony 7 with PHP 8.2 to 8.4, and with lowest dependencies
- `LocaleListener`'s internal placeholder is empty instead of `default`. On every Symfony line, `default, de` and
  `i-default, de` with `de` configured now give `de`, where 1.x kept the default locale; on Symfony 3.4 to 7.0,
  `i-, de` keeps the default locale, where 1.x gave `de`

### Fixed
- On Symfony 7.1 and later, `LocaleListener` kept the default locale when the language of a tag is the start of its
  internal `default` placeholder, which Symfony then matched first: `de-DE` with `de` configured, and from Symfony 7.3
  also `de`

### Security
- Where Symfony passes no annotation reader to the route loader (on Symfony 7, on Symfony 6.4 with
  `framework.annotations` disabled, and with FrameworkBundle 6.4 and `symfony/routing` 7), the bundle's docblock
  annotations, `@RequiredPermissions` among them, are ignored without an error unless `docblock_annotations` is `true`,
  as 1.x did on Symfony 6.4 with `framework.annotations` disabled. An application that moves to one of these setups
  from one where Symfony's reader applied them (Symfony 3.4 to 6.4 with `framework.annotations` enabled) must set the
  option to keep them
- Breaking: from Symfony 6.4, where the bundle reads docblock annotations (Symfony passes its annotation reader, or
  `docblock_annotations` is `true`), a route configured with both docblock annotations and attributes of the bundle, or
  of the application's own classes implementing its interfaces, on the action or on its class, fails to load with a
  `ConfigurationException`. Before, the attributes replaced all of the docblock options, so `#[Body]` on an action
  dropped the `@RequiredPermissions` of its class; configure each route with one of them. Where the bundle reads no
  docblock annotations, only the attributes apply, as the entry above says

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
