# Change Log

**3.0**
- `Paysera\Bundle\RestBundle\Entity\Error` all properties are now private instead of protected
- `Paysera\Bundle\RestBundle\Entity\Error` removed `errorCodes` property and related getters/setters
- `Paysera\Bundle\RestBundle\Entity\Error` removed `toArray` method
- `Paysera\Bundle\RestBundle\Exception\ApiException` all properties are private instead of protected
- `Paysera\Bundle\RestBundle\Exception\ApiException` 7th construct argument now is `violation` of type `Violation[]` instead of `codes` of type `string[]`
- `Paysera\Bundle\RestBundle\Exception\ApiException` removed property getter and setter of `codes` property
- 400 error response will no longer contain `errors` property instead if `error_properties_codes` property

**2.1**
- `_format` route attribute is no longer used to detect Request or Response format. 
`Content-Type` or `Accept` headers should be used accordingly. 

**2.0.3**
- Deprecated `_format` route attribute. In future releases request format will be always taken from `Content-Type` header.

**2.0**

- Update factory service syntax
