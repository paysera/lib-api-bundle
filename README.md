PayseraRestBundle ![](https://travis-ci.org/paysera/lib-rest-bundle.svg?branch=master)
=================

This bundle provides means for rapid API development.


Installation
------------
- Download bundle: `composer require paysera/lib-rest-bundle`
- Enable bundle: 
```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Paysera\Bundle\RestBundle\PayseraRestBundle(),
        );

        // ...
    }
}
```

Configuration
-----------
- Example of the full bundle configuration

```yaml
paysera_rest:
    property_path_converter: 'my_custom_path_converter' # Overrides the default path converter
    locales: ['en', 'ru', 'lt'] # Optional list of accepted locales
```

Basic Usage
-----------
- Create and configure your controller:
```php
class ApiController
{

    public function saveData(Data $data)
    {
        ...  
        return new CustomResponseEntity();
    }
}
```

```xml
<service id="app_bundle.controller.api_controller" class="AppBundle\Controller\ApiController" public="true">
    ...
</service>
```

- Create API service:
```xml
<service id="app_bundle.service.api_service" class="Paysera\Bundle\RestBundle\RestApi">
    <tag name="paysera_rest.api" api_key="my_custom_api_key"/>
    <argument type="service" id="service_container"/>
    <argument type="service" id="logger"/>
</service>
```

- Configure your routing and add `api_key`:
```xml
<route id="my_api_route.post_data" path="/resource" methods="POST">
    <default key="_controller">app_bundle.controller.api_controller:saveData</default>
    <default key="api_key">my_custom_api_key</default>
</route>
```

- Optionally, add request and response mappers to your API service:
```xml
<service id="app_bundle.service.api_service" class="Paysera\Bundle\RestBundle\RestApi">
    <tag name="paysera_rest.api" api_key="my_custom_api_key"/>
    <argument type="service" id="service_container"/>
    <argument type="service" id="logger"/>
    
    <call method="addRequestMapper">
        <argument>app_bundle.normalizer.data</argument>
        <argument>app_bundle.controller.api_controller:saveData</argument>
        <argument>data</argument>
    </call>
    
    <call method="addResponseMapper">
        <argument>app_bundle.normalizer.custom_response</argument>
        <argument>app_bundle.controller.api_controller:saveData</argument>
    </call>
</service>
```

Here `app_bundle.normalizer.data` is a service implementing `\Paysera\Component\Serializer\Normalizer\DenormalizerInterface`, `app_bundle.normalizer.custom_response` is a service implementing `\Paysera\Component\Serializer\Normalizer\NormalizerInterface`. Both of these services must be configured as public in their service definitions.
