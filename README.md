PayseraRestBundle
=================

This bundle provides means for rapid API development.


Installation
------------
- Download bundle: `composer require paysera/lib-rest-bundle`
- Enable bundle: 
```
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

Basic Usage
-----------
- Create and configure your controller:
```
class ApiController
{

    public function saveData(Data $data)
    {
        ...  
        return new CustomResponseEntity();
    }
}
```

```
<service id="app_bundle.controller.api_controller" class="AppBundle\Controller\ApiController">
    ...
</service>
```

- Create API service:
```
<service id="app_bundle.service.api_service" class="Paysera\Bundle\RestBundle\RestApi">
    <tag name="paysera_rest.api" api_key="my_custom_api_key"/>
    <argument type="service" id="service_container"/>
    <argument type="service" id="logger"/>
</service>
```

- Configure your routing and add `api_key`:
```
<route id="my_api_route.post_data" path="/resource" methods="POST">
    <default key="_controller">app_bundle.controller.api_controller:saveData</default>
    <default key="api_key">my_custom_api_key</default>
</route>
```

- Optionally, add request and response mappers to your API service:
```
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
