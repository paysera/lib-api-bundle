# Change Log

**2.1**
- `_format` route attribute is no longer used to detect Request or Response format. 
`Content-Type` or `Accept` headers should be used accordingly. 

**2.0.3**
- Deprecated `_format` route attribute. In future releases request format will be always taken from `Content-Type` header.

**2.0**

- Update factory service syntax
