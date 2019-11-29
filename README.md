# PayseraRestBundle

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

Symfony bundle that allows easy configuration for your REST endpoints.

## Why?

If you write quite many REST endpoints, some of the code or the structure itself gets repeated. If you want to add
some functionality to all your endpoints, this could also get troublesome if you just wrote some custom code for each
of them.

### Difference from API Platform

API Platform gives lots of API specification options, documentation generation and such.
This is available as it knows all the relations and fields in your objects.
But for that, you need to configure the objects for all these features, including serialization options.

This approach is perfect for small applications but can be a pain on larger ones or the ones that need long term support
and tend to change time to time.

When some custom functionality is needed, it's really easier to just implement it in code than to correctly configure it
(if such configuration is even available).

This bundle gives a bit more control:
- each route is defined explicitly and has controller action that's executed. This allows to track the execution better
and use any custom programming code when needed;
- for serialization/normalization code is used, not configuration. This makes it also more explicit and configurable.
Tightly coupling REST interface with business model does not seem as a good idea for us.

It's a bit more boilerplate, but easily customisable when needed.

## Installation

```bash
composer require paysera/lib-rest-bundle
```

If you're not using symfony flex, add the following bundles to your kernel:
```
new PayseraNormalizationBundle(),
new PayseraRestBundle(),
```

## Configuration

```yaml
paysera_rest:
    locales: ['en', 'lt', 'lv']        # Optional list of accepted locales
    validation:
        property_path_converter: your_service_id    # Optional service ID to use for property path converter
    path_attribute_resolvers:          # Registered path attribute resolvers. See below for more information
        App\Entity\PersistedEntity:
            field: identifierField
    pagination:
        total_count_strategy: optional # If should we provide or allow total count of resources (by default)
        maximum_offset: 1000           # If we should limit offset passed to pager for performance reasons
        maximum_limit: 1000            # Maximum limit for one page of results
        default_limit: 100             # Default limit for one page of results
```

## Usage

### Creating resource

To normalize and denormalize data from requests and to responses,
[PayseraNormalizationBundle](https://github.com/paysera/lib-normalization-bundle) is used.
It works by writing a class for each of your resource. This makes it explicit and allows easy customization
for mapping to/from your domain models (they usually are Doctrine entities).

Normalizer example:
```php
<?php
declare(strict_types=1);

use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\Normalization\NormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\Normalization\NormalizationContext;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class UserNormalizer implements ObjectDenormalizerInterface, NormalizerInterface, TypeAwareInterface
{
    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        return (new User())
            ->setEmail($input->getRequiredString('email'))
            ->setPlainPassword($input->getRequiredString('password'))
            ->setAddress($context->denormalize($input->getObject('address'), Address::class))
        ;
    }

    public function normalize($user, NormalizationContext $normalizationContext)
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'address' => $user->getAddress(),   // will be mapped automatically if type is classname
        ];
    }

    public function getType(): string
    {
        return User::class; // you can use anything here, but types can be guessed if FQCN are used
    }
}
```

In this case you'd also need to implement normalizer for `Address` class.

It's easiest to configure REST endpoints using annotations. This requires your routing to be provided in 
controller annotations, too.

Controller example:
```php
<?php
declare(strict_types=1);

use Symfony\Component\Routing\Annotation\Route;
use Paysera\Bundle\RestBundle\Annotation\Body;

class ApiController
{
    // ...

    /**
     * @Route("/users", method="POST")
     * @Body(parameterName="user")
     * 
     * @param User $user
     * @return User
     */
    public function register(User $user)
    {
        $this->securityChecker->checkPermissions(Permissions::REGISTER_USER, $user);
        
        $this->userManager->registerUser($user);
        $this->entityManager->flush();
        
        return $user;
    }
}
```

Don't forget to also import your controller (or `Controller` directory) into routing configuration. For example:

```xml
<!-- Resources/config/routing.xml -->
<import resource="../../Controller/" type="annotation" prefix="/rest/v1/"/>
```

```yaml
acme_something:
    resource: "@AcmeSomethingBundle/Controller/"
    type: annotation
    prefix: /rest/v1/
```

This also requires that your controller's service ID would be the same as its FQCN.

#### HTTP example

```
POST /rest/v1/users HTTP/1.1
Accept: */*
Host: api.example.com

{
    "email": "user1@example.com",
    "password": "that's my password",
    "address": {
        "country_code": "LT",
        "city": "Vilnius"
        "address_line": "Some street 1-2"
    }
}
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
    "id": 123,
    "email": "user1@example.com",
    "address": {
        "country_code": "LT",
        "city": "Vilnius"
        "address_line": "Some street 1-2"
    }
}
```

### Fetching resource

Controller example:
```php
<?php
declare(strict_types=1);

use Symfony\Component\Routing\Annotation\Route;
use Paysera\Bundle\RestBundle\Annotation\PathAttribute;

class ApiController
{
    // ...
    
    /**
     * @Route("/users/{userId}", method="GET")
     * @PathAttribute(parameterName="user", pathPartName="userId")
     * 
     * @param User $user
     * @return User
     */
    public function getUser(User $user)
    {
        $this->securityChecker->checkPermissions(Permissions::ACCESS_USER, $user);
        
        return $user;
    }
}
```

For path attributes `PathAttributeResolverInterface` should be implemented, as in this
case we receive just a scalar type (ID), not an object.

By default, bundle tries to find resolver with type registered as fully qualified class name of that parameter in the
type-hint.

You have at least few options to make this work.

1. Making your own path attribute resolver. For example:

```php
<?php
declare(strict_types=1);

use Paysera\Bundle\RestBundle\Service\PathAttributeResolver\PathAttributeResolverInterface;

class FindUserPathAttributeResolver implements PathAttributeResolverInterface
{
    // ...

    public function resolveFromAttribute($attributeValue)
    {
        return $this->repository->find($attributeValue);
    }
}
```

Tag service with `paysera_rest.path_attribute_resolver`, provide FQCN as `type` attribute.

2. Reusing `DoctrinePathAttributeResolver` class to configure the needed service. For example:

```xml
<service class="Paysera\Bundle\RestBundle\Service\PathAttributeResolver\DoctrinePathAttributeResolver"
         id="find_user_denormalizer">
    <tag name="paysera_rest.path_attribute_resolver" type="App\Entity\User"/>

    <argument type="service">
        <service class="App\Repository\UserRepository">
            <factory service="doctrine.orm.entity_manager" method="getRepository"/>
            <argument>App\Entity\User</argument>
        </service>
    </argument>
    <argument>id</argument><!-- or any other field to search by -->
</service>
```

3. Configuring supported classes and search fields in `config.yml`. This is practically the same as the previous option.

```yaml
paysera_rest:
    path_attribute_resolvers:
        App\Entity\User: ~  # defaults to searching by "id"
        App\Entity\PersistedEntity:
            field: identifierField
```

#### HTTP example

```
GET /rest/v1/users/123 HTTP/1.1
Accept: */*
Host: api.example.com
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
    "id": 123,
    "email": "user1@example.com",
    "address": {
        "country_code": "LT",
        "city": "Vilnius"
        "address_line": "Some street 1-2"
    }
}
```

### Fetching list of resources

Controller example:
```php
<?php
declare(strict_types=1);

use Symfony\Component\Routing\Annotation\Route;
use Paysera\Bundle\RestBundle\Annotation\Query;
use Paysera\Pagination\Entity\Pager;
use Paysera\Bundle\RestBundle\Entity\PagedQuery;

class ApiController
{
    // ...
    
    /**
     * @Route("/users", method="GET")
     * @Query(parameterName="filter")
     * @Query(parameterName="pager")
     * 
     * @param UserFilter $filter
     * @param Pager $pager
     * @return PagedQuery
     */
    public function getUsers(UserFilter $filter, Pager $pager)
    {
        $this->securityChecker->checkPermissions(Permissions::SEARCH_USERS, $filter);
        
        $configuredQuery = $this->userRepository->buildConfiguredQuery($filter);

        return new PagedQuery($configuredQuery, $pager);
    }
}
```

Denormalizer for `UserFilter`:
```php
<?php
declare(strict_types=1);

use Paysera\Component\Normalization\ObjectDenormalizerInterface;
use Paysera\Component\Normalization\TypeAwareInterface;
use Paysera\Component\Normalization\DenormalizationContext;
use Paysera\Component\ObjectWrapper\ObjectWrapper;

class UserFilterDenormalizer implements ObjectDenormalizerInterface, TypeAwareInterface
{
    public function denormalize(ObjectWrapper $input, DenormalizationContext $context)
    {
        return (new UserFilter())
            ->setEmail($input->getString('email'))
            ->setCountryCode($input->getString('country_code'))
        ;
    }

    public function getType(): string
    {
        return UserFilter::class; // you can use anything here, but types can be guessed if FQCN are used
    }
}
```

Code in `UserRepository`:
```php
<?php
declare(strict_types=1);

use Paysera\Pagination\Entity\OrderingConfiguration;
use Paysera\Pagination\Entity\Doctrine\ConfiguredQuery;

class UserRepository extends Repository
{
    public function buildConfiguredQuery(UserFilter $filter)
    {
        // just an example – should add conditions only when they're set
        $queryBuilder = $this->createQueryBuilder('u')
            ->join('u.address', 'a')
            ->join('a.country', 'c')
            ->andWhere('u.email = :email')
            ->andWhere('c.code = :countryCode')
            ->setParameter('email', $filter->getEmail())
            ->setParameter('countryCode', $filter->getCountryCode())
        ;
        
        return (new ConfiguredQuery($queryBuilder))
            ->addOrderingConfiguration('email', new OrderingConfiguration('u.email', 'email'))
            ->addOrderingConfiguration(
                'country_code',
                new OrderingConfiguration('c.code', 'address.country.code')
            )
        ;
    }
}
```

As seen in this example, bundle integrates support for
[Paysera Pagination component](https://github.com/paysera/lib-pagination).

In this case, actual database fetch is performed in the normalizer itself. This is done due to several reasons:
- to allow configuring total count strategy and maximum offset for the whole application (see below);
- to support optional total count and optional items. By default, if client does not explicitly ask for total
count of resources, it's not calculated.

Configuration example:

```yaml
paysera_rest:
    pagination:
        total_count_strategy: optional
        maximum_offset: 1000    # could be set to null for no limit 
        maximum_limit: 500      # can be configured to any number but cannot be null
        default_limit: 100      # used if no limit parameter was passed
```

Overriding options for specific actions:

```php
    // ... begining of controller action

    $configuredQuery = $this->userRepository->buildConfiguredQuery($filter);
    $configuredQuery->setMaximumOffset(1000); // optionally override maximum offset
    
    return (new PagedQuery($configuredQuery, $pager))
        // optionally override total count strategy
        ->setTotalCountStrategy(PagedQuery::TOTAL_COUNT_STRATEGY_OPTIONAL)
    ;
```

Available strategies:
- `always` – total count is calculated by default, unless explicitly excluded from returned fields;
- `optional` – total count is not calculated by default, but could, if explicitly included in returned fields;
- `never` – total count is never calculated;
- `default` – available only for `PagedQuery`, falls back to globally configured strategy.

When you explicitly set `always` strategy in `ConfiguredQuery` object, maximum offset will be ignored. If you
still need it, configure it explicitly in `ConfiguredQuery`, just like the strategy itself.

If you use some other strategy and configure maximum offset, there's currently no way to allow any offset for an
endpoint explicitly.

#### Request and response structures

Pager is denormalized from the following query string fields:
- `limit`. Limits count of resources in a page. Defaults to configured `default_limit` value;
- `offset`. Skips some number of results. Should only be used to go to Nth page. Is restrained to `maximum_offset`
value by default;
- `after`. Accepts cursor from previous result to provide "next page" of results;
- `before`. Accepts cursor from previous result to provide "previous page" or results;
- `sort`. Accepts list of fields, configured by `ConfiguredQuery::addOrderingConfiguration`, separated by comma. To
order descending, prefix specific item with `-`. For example, `?sort=-date_of_birth,registered_at` would result in 
something like `ORDER BY date_of_birth DESC, registered_at ASC`.

Only one of `ofset`/`after`/`before` can be provided.

Response structure has the following fields:
- `items`. Array of normalized resources;
- `_metadata.total`. Integer, total count of resources. Missing by default, this depends on strategy and `fields`
parameter in query string;
- `_metadata.has_next`. Boolean, whether next page is currently available;
- `_metadata.has_previous`. Boolean, whether previous page is currently available;
- `_metadata.cursors.after`. String, pass as `after` parameter in query string to get next page;
- `_metadata.cursors.before`. String, pass as `before` parameter in query string to get previous page.

Keep in mind that cursors could be missing in some rare cases (for example, no results at all).
On another hand, they are provided even if there currently is no next/previous page. This could be used to check
if any new resources were created – quite handy when used for synchronizing with backend.

Don't make any assumptions about internal structure of cursor value, as this could change with any release. 

#### HTTP examples

```
GET /rest/v1/users?limit=2 HTTP/1.1
Accept: */*
Host: api.example.com
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
    "items": [
        {
            "id": 1,
            "email": "user1@example.com",
            "address": {
                "country_code": "LT",
                "city": "Vilnius"
                "address_line": "Some street 1-2"
            }
        },
        {
            "id": 2,
            "email": "user2@example.com",
            "address": {
                "country_code": "LT",
                "city": "Kaunas"
                "address_line": "Some street 2-3"
            }
        }
    ],
    "_metadata": {
        "has_next": true,
        "has_previous": false,
        "cursors": {
            "after": "\"2-abc\"",
            "before": "\"1-abc\""
        }
    }
}
```

To get only total count:
```
GET /rest/v1/users?fields=_metadata.total HTTP/1.1
Accept: */*
Host: api.example.com
```

```
HTTP/1.1 200 OK
Content-Type: application/json

{
    "_metadata": {
        "total": 15
    }
}
```

This will actually make only the `SELECT` statement for the total count, no items will be selected.

To get page of resources with the total count:
```
GET /rest/v1/users?fields=*,_metadata.total HTTP/1.1
Accept: */*
Host: api.example.com
```

To get the next page:
```
GET /rest/v1/users?after="2-abc" HTTP/1.1
Accept: */*
Host: api.example.com
```

`"2-abc"` is taken from `_metadata.cursors.after` in this case.

Let's assume that resources are ordered by creation date in descending order.
To get if any new resources were created:
```
GET /rest/v1/users?before="1-abc" HTTP/1.1
Accept: */*
Host: api.example.com
```

In this case, if there would be zero results, `_metadata.cursors.before` would still be the same. Saving last
cursor and iterating this way until we have `"has_previous": false` is a reliable way to synchronize resources.

## Annotations reference

### `Body`

Instructs to convert request body into an object and pass to the controller as an argument.

| Option name           | Default value                         | Description
|-----------------------|---------------------------------------|-------------------------------------------------------------------------------------------------------|
| `parameterName`       | Required                              | Specifies parameter name (without `$` in controller action for passing denormalized data              |
| `denormalizationType` | Guessed from parameter's type-hint    | Denormalization type to use for body data denormalization                                             |
| `optional`            | `true` if parameter has default value | Allows overwriting requirement for request body. Optional means that empty request body can be passed |

### `BodyContentType`

Configuration for allowed request content types and whether body should be JSON-decoded before passing
to denormalizer.

| Option name             | Default value | Description                                                                                                                                                                       |
|-------------------------|---------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `supportedContentTypes` | Required      | Array of supported Content-Type values. Can be `*` for any content type or `something/*` for validating only the first part. For example, this can be useful to allow only images |
| `jsonEncodedBody`       | `false`       | Whether content should be JSON-decoded before passing to denormalizer                                                                                                             |

If not configured, defaults to JSON-encoded body and 2 allowed Content-Type values: `""` (empty) and `"application/json"`.

For this annotation to have any effect, `Body` annotation must be present. Provide `plain` as `denormalizationType`
if you want denormalization process to be skipped.

### `Validation`

Configures or switches off validation for object, denormalized from request body.
By default, validation is always enabled.

You can turn it off for an action or whole controller class.

If annotation is provided on both class and action, the one on action "wins" – they are not merged together.

| Option name        | Default value | Description                                                                                                                                                                  |
|--------------------|---------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `enabled`          | `true`        | Allows to explicitly disable the validation                                                                                                                                  |
| `groups`           | `['Default']` | Validation groups to be used when validating. Empty list of groups is the same as disabled validation                                                                        |
| `violationPathMap` | `[]`          | Associative array to convert propertyPath into REST fields. By default, camelCase is already converted to underscore_case. Use this if you have naming or structure mismatch |

### `ResponseNormalization`

Configures normalization type to use for method's return value, if you'd need custom one.

By default, REST endpoints try to normalize any value returned from controller's action if it's not a `Response` object.

If nothing is returned from the method (`void`), empty response with HTTP status code `204` is provided.

| Option name         | Default value             | Description                                                     |
|---------------------|---------------------------|-----------------------------------------------------------------|
| `normalizationType` | Guessed from return value | Normalization type to use for normalizing method's return value |

### `PathAttribute`

Configures denormalization for some concrete part of the path. Usually used to find entities by their IDs.

Multiple such annotations can be used in a single controller's action.

| Option name           | Default value                            | Description                                                                                                                    |
|-----------------------|------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `parameterName`       | Required                                 | Specifies parameter name (without `$` in controller action for passing denormalized data                                       |
| `pathPartName`        | Required                                 | Specifies routing attribute to use for denormalization (for `/users/{id}` use `id`)                                            |
| `denormalizationType` | Guessed from type-hint + `:find` suffix  | Allows configuring custom denormalization type to use. Registered denormalizer must implement `MixedTypeDenormalizerInterface` |
| `resolutionMandatory` | `true` if parameter has no default value | Specifies whether `404` error should be returned if parameter is not resolved                                                  |

### `Query`

Instructs to convert query string into an object and pass to the controller as an argument.

Multiple annotations can be used to map several different objects.

| Option name           | Default value                     | Description                                                                                                                    |
|-----------------------|-----------------------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `parameterName`       | Required                          | Specifies parameter name (without `$` in controller action for passing denormalized data                                       |
| `denormalizationType` | Guessed from type-hint            | Allows configuring custom denormalization type to use. Registered denormalizer must implement `MixedTypeDenormalizerInterface` |
| `validation`          | Enabled with `['Default']` groups | Use another `@Validation` annotation here, just like when configuring validation for request body                              |

### `RequiredPermissions`

Instructs to check for permissions in security context for that specific action.

Could be also added in the class level.
Permissions from class and method level annotations are merged together.

| Option name   | Default value | Description                                                              |
|---------------|---------------|--------------------------------------------------------------------------|
| `permissions` | Required      | List of permissions to be checked before any denormalization takes place |

## Configuration without using annotations

It's also possible to configure options defining `RestRequestOptions` as a service
and tagging it with `paysera_rest.request_options`.

Example:
```xml
<service id="paysera_fixture_test.rest_request_options.1"
         class="Paysera\Bundle\RestBundle\Entity\RestRequestOptions">
    <tag name="paysera_rest.request_options" controller="service_id::action"/>
    <tag name="paysera_rest.request_options" controller="App\Controller\DefaultController::action"/>

    <!-- set any options similarly to this -->
    <call method="addQueryResolverOptions">
        <argument type="service">
            <service class="Paysera\Bundle\RestBundle\Entity\QueryResolverOptions">
                <call method="setDenormalizationType">
                    <argument>extract:parameter</argument>
                </call>
                <call method="setParameterName">
                    <argument>parameter</argument>
                </call>
            </service>
        </argument>
    </call>
</service>
```

## Semantic versioning

This library follows [semantic versioning](http://semver.org/spec/v2.0.0.html).

See [Symfony BC rules](http://symfony.com/doc/current/contributing/code/bc.html) for basic
information about what can be changed and what not in the API.

Please do not use any services not marked with `public="true"` and any classes or methods marked with `@internal`
as these could change in any release.

## Running tests

```
composer update
composer test
```

## Contributing

Feel free to create issues and give pull requests.

You can fix any code style issues using this command:
```
composer fix-cs
```

[ico-version]: https://img.shields.io/packagist/v/paysera/lib-rest-bundle.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/paysera/lib-rest-bundle/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/paysera/lib-rest-bundle.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/paysera/lib-rest-bundle.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/paysera/lib-rest-bundle.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/paysera/lib-rest-bundle
[link-travis]: https://travis-ci.org/paysera/lib-rest-bundle
[link-scrutinizer]: https://scrutinizer-ci.com/g/paysera/lib-rest-bundle/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/paysera/lib-rest-bundle
[link-downloads]: https://packagist.org/packages/paysera/lib-rest-bundle
