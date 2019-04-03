# Change Log

## 4.3.0
### Added
- `Paysera\Bundle\RestBundle\ApiManager::createErrorFromException()` - checks if exception contains status code *400* and creates `ApiException::InvalidRequest` error

## 4.2.2
### Fixed
- `Paysera\Bundle\RestBundle\Listener\RestListener::onKernelException()` increased listener priority to `10`. Since Symfony 3.3 the priority of `ExceptionListener::onKernelException` was changed to `1`.

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
