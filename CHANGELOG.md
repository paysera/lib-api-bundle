# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]
### Added
- Support for Symfony 7.4 and `psr/log` 3; CI runs PHP 8.4, Symfony 7 and lowest dependencies
- Explicit requirements for the Symfony components the bundle uses directly: `symfony/config`,
  `dependency-injection`, `http-foundation`, `http-kernel`, `property-access`, `routing` and `security-core`

### Changed
- `Configuration::getConfigTreeBuilder()` declares its `TreeBuilder` return type, as Symfony 7 requires, and the class
  is `@final`. Breaking for a subclass that overrides the method without the return type
- `PayseraApiExtension` extends `Symfony\Component\DependencyInjection\Extension\Extension` instead of HttpKernel's
  `Extension`, which is internal since Symfony 7.1. Breaking for a subclass that calls `addAnnotatedClassesToCompile()`
- Services are registered by `PayseraApiExtension` instead of the XML files in `Resources/config`, which are removed,
  because Symfony 7.4 deprecates XML configuration. Service ids, classes, arguments, calls and tags are unchanged,
  except that both internal route loaders take the new `paysera_api.rest_route_configurator` service (with the new
  `paysera_api.docblock_annotation_reader`) instead of the request helper and the options builders
- An error while the bundle reads a route's docblock annotations itself or builds the bundle's attributes, from an
  annotation or attribute constructor too, is wrapped in a `ConfigurationException` naming the action. Where Symfony
  passes a reader, its own loader reads most docblocks with it first, so their errors come from Symfony unwrapped, and a
  class with a missing parent (before PHP 7.3) or trait (any version) still stops PHP. An attribute named under
  `Paysera\Bundle\ApiBundle\` that is none of the bundle's attributes (a misspelt name or namespace) fails too, and so
  does an attribute whose class cannot be loaded and whose name, without its namespace, is one of the bundle's
  attributes: one written without its `use` statement, and also one of a package that is not installed where the routes
  are loaded, such as swagger-php's `#[OA\Query]` with swagger-php installed for development only (install the package
  there, or remove the attribute)
- Optional parameters are declared nullable explicitly (`?Type $parameter = null`), as PHP 8.4 expects
- `doctrine/annotations` is required at 1.14.4 or 2.0.2 and later, whose reader refuses to run with
  `opcache.save_comments=0` instead of reading the stripped docblocks as empty
- `LocaleListener` marks "no configured locale matched" with a `,` placeholder instead of `default`, which no unquoted
  `Accept-Language` item can contain. A header listing the language `default` or `i-default` before a configured locale
  now selects that locale, where 1.x kept the default locale

### Fixed
- On Symfony 7.1 and later, `LocaleListener` kept the default locale for `de-DE` (and for `de` from Symfony 7.3) with
  `de` configured, because Symfony matched the language against the placeholder `default` first

### Security
- Where Symfony passes no annotation reader to the route loader (Symfony 7, and Symfony 5.4 on PHP 8 or Symfony 6 with
  `framework.annotations` disabled), 1.x ignored the bundle's docblock annotations, `@RequiredPermissions` among them,
  without an error on Symfony 6.4, and failed to load any route before it. The bundle now reads them with Doctrine's
  `AnnotationReader` where Doctrine would find them: where a docblock uses an annotation implementing
  `RestAnnotationInterface` (the bundle's or the application's), as Doctrine's `DocParser` resolves it, from the imports
  and namespace of the class for its docblock, and of the declaring class and the traits in the action's file for an
  action's docblock; and where the routed class, the declaring class or a trait in the action's file imports
  `Paysera\Bundle\ApiBundle\Annotation`. Code that uses only attributes is not read with that reader, unless Doctrine's
  parser fails on one of its docblocks (a tag naming a class that cannot be loaded), and an unused import of the
  namespace makes the file read too, so remove it. Loading the routes fails with a `ConfigurationException` naming the
  action where Doctrine's reader fails, on a docblock tag it cannot resolve (import it, or list it with
  `@IgnoreAnnotation` in the class docblock). Where OPcache is set to strip doc comments (`save_comments=0`), Doctrine's
  reader refuses to run, so loading fails where those classes import the annotation namespace. Not detected in a file
  that does not import `Paysera\Bundle\ApiBundle\Annotation`: an annotation that Doctrine cannot resolve (written with
  no import, with a misspelt import such as `Annotations`, or misspelt) and, where OPcache has stripped the doc
  comments, any annotation
- On PHP 8 before Symfony 6.4, 1.x ignored the bundle's attributes, `#[RequiredPermissions]` among them, without an
  error. They are now applied on every supported Symfony version
- On PHP 8, a bundle attribute written without its `use` statement, `#[RequiredPermissions]` among them, names a class
  that does not exist in the namespace it is written in, which PHP and 1.x skip without an error. Loading the routes
  now fails with a `ConfigurationException` naming the action and the bundle's attribute of that name; a class of that
  name that can be loaded, such as the application's own `Body` attribute, is left alone. Not detected: an attribute
  written without its `use` statement whose name is none of the bundle's seven, such as a misspelt one
  (`#[RequiredPermission]`) or the application's own attribute, and an alias of a class that does not exist
  (`use App\Missing as RequiredPermissions`)
- On PHP 8, a route configured with both docblock annotations and attributes of the bundle, on the action or on its
  class, fails to load with a `ConfigurationException`. Before, the attributes silently replaced the docblock options
  from Symfony 6.4, so `#[Body]` on an action dropped the `@RequiredPermissions` of its class, and were ignored before
- These fixes are not backported to 1.x. 1.x still drops the bundle's permission checks without an error on Symfony 6.4
  with `framework.annotations` disabled, on PHP 8 before Symfony 6.4 for the bundle's attributes, on routes configured
  with both, and for an attribute written without its `use` statement. Upgrade to 2.0.0, which applies the checks there
  or fails to load the routes where it cannot

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
