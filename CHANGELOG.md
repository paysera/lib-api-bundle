# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0]
### Added
- Support for Symfony 5.4

### Changed
- `symfony/framework-bundle` bumped to `^5.4`
- `symfony/security-bundle` bumped to `^5.4`
- `symfony/validator` bumped to `^5.4`
- `paysera/lib-normalization-bundle` bumped to `^5.4`

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
