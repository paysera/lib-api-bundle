# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Changed
- Totally refactored the internals and configuration of this bundle. Please use paysera/lib-rest-migration-bundle
to still use older version of configuration. For new endpoints, use new configuration format from README.

  - Configuration is moved from service level (XML) to routing level (Annotations). This also requires
  to configure routing using annotations, too.
  - Normalization and denormalization has changed – lib-normalization-bundle is used instead of lib-serializer.
  This also allows guessing of de/normalizers based on object classes or controller type-hints.
  - Query string can be denormalized into multiple separate objects.
  - `Filter` and `Result` from `lib-serializer` should not be used anymore (as anything else from that library).
  `Pager` and `Result` from `lib-pagination` should be used instead, but there's no need in extending them
  or additionally configuring in any way. `YourCustomFilter` and `Pager` objects are passed separately to Controller.
  It's also best to use cursor-based pagination and avoid providing total count. This is far more easier now.
  See README.md for examples.
  
- `property_path_converter` configuration option was moved to `validation` group

- `Paysera\Bundle\RestBundle\Entity\Error` and `Paysera\Bundle\RestBundle\Exception\ApiException`
should now receive and operate `Paysera\Bundle\RestBundle\Entity\Violation` instead of 
`Paysera\Component\Serializer\Entity\Violation`.

- `Paysera\Bundle\RestBundle\Entity\Error::create` method removed, constructor also does not take
any arguments anymore.

- `Paysera\Bundle\RestBundle\Normalizer\ErrorNormalizer` class changed to implement interfaces from
`lib-normalization` instead of `lib-serializer`. If you still need it, change usages from
`paysera_rest.normalizer.error` to `paysera_rest_migration.normalizer.error` when upgrading.

### Removed
- Support for encoders – if you return non-JSON content, just return plain symfony Response object.
- Support for caching configuration. This usually does not work in any case when authentication is needed
and response cannot be cached inside the browser for security reasons.
- Resolving locale from query was removed – it's resolved only from Accept-Language headers.
- Security strategy support was removed – configure required permissions instead and use voters.

## 4.2.1
### Changed
- `\Paysera\Bundle\RestBundle\RestApi::getValidationGroups()` no longer returns `null` if `\Paysera\Bundle\RestBundle\RestApi::$globalValidationGroups` is empty.

## 4.2.0
### Added
- New optional bundle configuration parameter `locales`

### Changed
- `\Symfony\Component\HttpFoundation\Request::getLocale` now can return preferred locale from the `Accept-Language` header, which resolves from the `locales` parameter in the bundle configuration

## 4.1.1
### Added
- Moved `Paysera\Bundle\RestBundle\Listener\RestListener::onKernelException` logging to service `Paysera\Bundle\RestBundle\Service\ExceptionLogger`

## 4.0.0
###  Changed
- `Paysera\Bundle\RestBundle\RestApi` property `propertyPathConverter` now has default value set to `CamelCaseToSnakeCaseConverter`

## 3.0.0
### Changed 
- `Paysera\Bundle\RestBundle\Entity\Error` all properties are now private instead of protected
- `Paysera\Bundle\RestBundle\Exception\ApiException` all properties are private instead of protected
- `Paysera\Bundle\RestBundle\Exception\ApiException` 7th construct argument now is `violation` of type `Violation[]` instead of `codes` of type `string[]`
- 400 error response will no longer contain `errors` property instead if `error_properties_codes` property
### Removed
- `Paysera\Bundle\RestBundle\Entity\Error` removed `errorCodes` property and related getters/setters
- `Paysera\Bundle\RestBundle\Entity\Error` removed `toArray` method
- `Paysera\Bundle\RestBundle\Exception\ApiException` removed property getter and setter of `codes` property

## 2.1.0
### Changed
- `_format` route attribute is no longer used to detect Request or Response format. 
`Content-Type` or `Accept` headers should be used accordingly. 

## 2.0.3
### Deprecated
- Deprecated `_format` route attribute. In future releases request format will be always taken from `Content-Type` header.

## 2.0.0
### Changed
- Update factory service syntax
