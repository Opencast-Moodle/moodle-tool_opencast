# opencast-php-library
This PHP composer package is meant to provide a unified easy-to-use Opencast RESTful API library. It has been designed to make most of commonly used REST Endpoints available to the developers of thirt-party applications mainly LMSes such as Stud.IP, Moodle and ILIAS.
Please refer to the [Change Log](https://github.com/elan-ev/opencast-php-library/blob/master/CHANGELOG.md) for more info.

## Note
As of version 1.2.0 the main class name has been changed from `OpenCast` to `OpenCast`. Please refer to [Upgrade Log](https://github.com/elan-ev/opencast-php-library/blob/master/UPGRADING.md) for more info.

# Requisitions
<b>PHP Version 7.2.5 or above</b> as well as <b>cURL</b> are required. Additionaly, the [requirements](https://docs.guzzlephp.org/en/stable/overview.html#requirements) of [guzzlehttp/guzzle](https://packagist.org/packages/guzzlehttp/guzzle#7.0.0) must be fullfiled.

# Installation
`composer require elan-ev/opencast-api`

# Basic Usage
There are 2 approaches to use the Opencast REST Endpoints from this library:

1. The first one is via the generic `OpencastApi\Opencast` which contains all available Opencast endpoints (which are capable with the API version defined in the config). The advantage of using this approach is a better control over all available endpoints. <b>(Recommended)</b>

<b>NOTE:</b> When using this library against a distributed Opencast setup with admin and presentation split, you can pass another set of configuration as the second parameter when instantiating the `OpencastApi\Opencast`. Initially, the presentation node only takes care of search endpoint.
```php
$config = [
      'url' => 'https://develop.opencast.org/',       // The API URL of the Opencast instance. (required)
      'username' => 'admin',                          // The API username. (required)
      'password' => 'opencast',                       // The API password. (required)
      'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
      'connect_timeout' => 0,                         // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
      'version' => null,                              // The API Version. (Default null). (optional)
      'handler' => null                               // The Mock Response Handler with Closure type. (Default null). (optional)
];

$engageConfig = [
      'url' => 'https://develop.opencast.org/',       // The API URL of the Opencast instance. (required)
      'username' => 'admin',                          // The API username. (required)
      'password' => 'opencast',                       // The API password. (required)
      'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
      'connect_timeout' => 0,                         // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
      'version' => null,                              // The API version. (Default null). (optional)
      'handler' => null                               // The Mock Response Handler with Closure type. (Default null). (optional)
];

use OpencastApi\Opencast;

// In case of a distributed Opencast setup
$opencastDualApi = new Opencast($config, $engageConfig);
// Or simply 
$opencastApi = new Opencast($config);

// Accessing Event Endpoints to get all events
$events = [];
$eventsResponse = $opencastApi->eventsApi->getAll();
if ($eventsResponse['code'] == 200) {
      $events = $eventsResponse['body'];
}

// Accessing Series Endpoints to get all series
$series = [];
$seriesResponse = $opencastApi->seriesApi->getAll();
if ($seriesResponse['code'] == 200) {
      $series = $seriesResponse['body'];
}

// ...
```

2. The second approach is to instantiate each REST endpoint class, which are located under `OpencastApi\Rest\` namespace, when needed, but the down side of this is that it needs a `OpencastApi\Rest\OcRestClient` instance as its parameter. The advantage of this approach might be the methods' definitions in the IDE.

```php
$config = [
      'url' => 'https://develop.opencast.org/',       // The API URL of the Opencast instance. (required)
      'username' => 'admin',                          // The API username. (required)
      'password' => 'opencast',                       // The API password. (required)
      'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
      'connect_timeout' => 0,                         // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
      'version' => null,                              // The API version. (Default null). (optional)
      'handler' => null                               // The Mock Response Handler with Closure type. (Default null). (optional)
];


use OpencastApi\Rest\OcRestClient;
use OpencastApi\Rest\OcEventsApi;
use OpencastApi\Rest\OcSeriesApi;

// Get a client object.
$opencastClient = new OcRestClient($config);

// To get events.
$opencastEventsApi = new OcEventsApi($opencastClient);
$events = [];
$eventsResponse = $opencastEventsApi->getAll();
if ($eventsResponse['code'] == 200) {
      $events = $eventsResponse['body'];
}

// To get series.
$opencastSeriesApi = new OcSeriesApi($opencastClient);
$series = [];
$seriesResponse = $opencastSeriesApi->getAll();
if ($seriesResponse['body'] == 200) {
      $series = $seriesResponse['body'];
}

// ...
```
# Configuration
The configuration is type of `Array` and has to be defined as follows:
```php
$config = [
      'url' => 'https://develop.opencast.org/',       // The API URL of the Opencast instance. (required)
      'username' => 'admin',                          // The API username. (required)
      'password' => 'opencast',                       // The API password. (required)
      'timeout' => 0,                                 // The API timeout. In seconds (default 0 to wait indefinitely). (optional)
      'connect_timeout' => 0,                         // The API connection timeout. In seconds (default 0 to wait indefinitely) (optional)
      'version' => null,                              // The API version. (Default null). (optional)
      'handler' => null                               // The Mock Response Handler with Closure type. (Default null). (optional)
];
```
NOTE: the configuration for presentation (`engage` node) responsible for search has to follow the same definition as normal config. But in case any parameter is missing, the value will be taken from the main config param.

#### Extra: Dynamically loading the ingest endpoint class into Opencast instance.
As of v1.3 it is possible to enable (Default) or disable  the ingest endpoint to be loaded into `OpencastApi\Opencast` by passing a boolean value as the last argument of the class as follows:

```php
use OpencastApi\Opencast;
$opencastApiWithIngest = new Opencast($config, $engageConfig);
$opencastApiWithoutIngest = new Opencast($config, $engageConfig, false);
// ...
```

# Response
The return result of each call is an `Array` containing the following information:
```php
[
      'code' => 200,                // The status code of the response
      'body' => '',                 // The result of the response. It can be type of string, array or object ('' || [] || {})
      'reason' => 'OK',             // The reason/message of response
      'location' => '',             // The location header of the response when available
]
```
# Filters and Sorts
Filters and Sorts must be defined as associative `Array`, as follows:
```php
// for example:

$filters = [
      'title' => 'The title name',
      'creator' => 'opencast admin'
];

$sorts = [
      'title' => 'DESC',
      'startDate' => 'ASC'
];
```
<b>NOTE:</b> Sometimes a filter can occur multiple times for example in [Series API `/get`](https://docs.opencast.org/develop/developer/#api/series-api/#get-apiseries), filters like `subject` and `identifier` can occur multiple times. Therefore, an `Array` should be passed as filter value like following:
```php
// for example:

$filters = [
      'identifier' => [
            '{first ID}',  '{second ID}'
      ],
      'subject' => [
            '{first Subject}',  '{second Subject}'
      ]
];

```

# `runWithRoles([])`
Sometimes it is needed to perform the request with a disposable header of `X-RUN-WITH-ROLES` containing some roles (e.g. user ids) in order for Opencast to assume that those users has special access right.<br />It is commonly used to get data with `onlyWithWriteAccess` parameter for example to perform [`getAll`](https://github.com/elan-ev/opencast-php-library/wiki/OcSeriesApi#getallparams--) in `OcSeriesApi` and grab those series that only specified users have access to!<br />In order to perform such requests it is needed to call the `runWithRoles` method <b>before</b> calling the desired function in a class:<br />
NOTE: This method <b>accepts</b> an `Array` defining the roles to check against!
```php
// With Opencast generic class
use OpencastApi\Opencast;
$opencastApi = new Opencast($config);

// Role
$roles = ['ROLE_ADMIN'];
$seriesResponse = $opencastApi->seriesApi->runWithRoles($roles)->getAll(['onlyWithWriteAccess' => true]);

// Or direct class call
$opencastClient = \OpencastApi\Rest\OcRestClient($config);
$ocSeriesApi = \OpencastApi\Rest\OcSeriesApi($opencastClient);
// Role
$roles = ['ROLE_ADMIN'];
$seriesResponse = $ocSeriesApi->runWithRoles($roles)->getAll(['onlyWithWriteAccess' => true]);
```
<b>NOTE:</b> Roles can be either an `Array` including each role, or a comma separated string!

# `noHeader()`
In order to perform a request call to an endpoint without any request headers/options, you can use this method <b>before</b> calling the desired function in an endpoint class:
NOTE: This method <b>accepts</b> nothing (`void`).<br />
```php
// With Opencast generic class
use OpencastApi\Opencast;
$opencastApi = new Opencast($config);

$baseResponse = $opencastApi->baseApi->noHeader()->get();

// Or direct class call
$opencastClient = \OpencastApi\Rest\OcRestClient($config);
$ocBaseApi = \OpencastApi\Rest\OcBaseApi($opencastClient);
$baseResponse = $ocBaseApi->noHeader()->get();
```

# `setRequestTimeout($timeout = 0)`
In order to perform a request call with a different timeout value other than the one set in the configuration, you can use this method <b>before</b> calling the desired function in an endpoint class:
NOTE: This method <b>accepts</b> integer defining a single use timeout in second.<br />
```php
// With Opencast generic class
use OpencastApi\Opencast;
$opencastApi = new Opencast($config);

$baseResponse = $opencastApi->baseApi->setRequestTimeout(10)->get();

// Or direct class call
$opencastClient = \OpencastApi\Rest\OcRestClient($config);
$ocBaseApi = \OpencastApi\Rest\OcBaseApi($opencastClient);
$baseResponse = $ocBaseApi->setRequestTimeout(10)->get();
```

# `setRequestConnectionTimeout($connectionTimeout = 0)`
In order to perform a request call with a different connection timeout value other than the one set in the configuration, you can use this method <b>before</b> calling the desired function in an endpoint class:
NOTE: This method <b>accepts</b> integer defining a single use connection timeout in second.<br />
```php
// With Opencast generic class
use OpencastApi\Opencast;
$opencastApi = new Opencast($config);

$baseResponse = $opencastApi->baseApi->setRequestConnectionTimeout(10)->get();

// Or direct class call
$opencastClient = \OpencastApi\Rest\OcRestClient($config);
$ocBaseApi = \OpencastApi\Rest\OcBaseApi($opencastClient);
$baseResponse = $ocBaseApi->setRequestConnectionTimeout(10)->get();
```

# Available Opencast REST Service Endpoint

- `/api/*`: all known API endpoints of Opencast are available to be used in this library. [API Endpoints definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/API-Endpoints)
  
- `/ingest/*`: all known Ingest endpoints are available. [Ingest Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcIngest)

- `/services/services.json`: only services.json is available. [Services Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcServices)

- `/search/{episode | lucene | series}.{json | xml}`: only episode, lucene and series in JSON or XML format are available. [Search Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcSearch)

- `/capture-admin/*`: all known Capture Admin endpoints are available. [Capture Admin Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcCaptureAdmin)

- `/admin-ng/event/delete`: only delete endpoint is available. [Admin Ng Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcEventAdminNg)

- `/recordings/*`: all known Recording endpoints are available. [Recordings Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcRecordings)

- `/series/*`: all known Series endpoints are available. [Series Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcSeries)

- `/workflow/*`: all known Workflow endpoints are available. [Workflow Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcWorkflow)

- `/sysinfo/bundles/version`: (v1.1.1) only bundle version endpoint is available. [Sysinfo Endpoint definitions WiKi](https://github.com/elan-ev/opencast-php-library/wiki/OcSysinfo)

# Mocking Responses
In order to conduct proper testing, a mocking mechanism is provided.
## How to use
### Step 1: Responses Array
As in the first step it is necessary to have all the responses in an array form, which has to have the following structure:

```php
$responsesArray = [
      "/api/events" => [ // Request Path
            "GET": [ // METHOD
                  [
                        "body" => "", //Response body (Default "")
                        "status": 200, // Status code (Default 200)
                        "headers": [], // Response headers (Default [])
                        "version": "", // Protocol version (Default null)
                        "reason": "", // Reason phrase (when empty a default will be used based on the status code) (Default null)
                        "params": []  // The optional params to pass to the call (Default [])
                  ]
            ],
            "POST": [ // METHOD
                  [
                        "body" => "", //Response body (Default "")
                        "status": 200, // Status code (Default 200)
                        "headers": [], // Response headers (Default [])
                        "version": "", // Protocol version (Default null)
                        "reason": "", // Reason phrase (when empty a default will be used based on the status code) (Default null)
                        "params": []  // The optional params to pass to the call (Default [])
                  ],
                  [...] // Multiple response data object to have multiple calls to the same path and same method. Please see the NOTE below.
            ],
            "PUT": [
                  [...]
            ],
            "DELETE": [
                  [...]
            ]
      ],
];

```
NOTE: In order to apply multiple calls to the same path, a unique parameter called `unique_request_identifier` in `params` array must be provided, for example you want to update a series's acl twice with the same path but different acl values: (The value of the unique identifier must be something within the body of the request)

```php
$responsesArray = [
      "/api/series/{series id}/acl": [
            "PUT": [ // PUT METHOD on the same path
                  [
                        "body" => "", //Response body (Default "")
                        "status": 200, // Status code (Default 200)
                        "params": [  // The optional params to pass to the call (Default [])
                              "unique_request_identifier": "acl=[]"
                        ],
                  ],
                  [
                        "body" => "[{\"allow\":true,\"role\":\"ROLE_ANONYMOUS\",\"action\":\"read\"}]", //Response body (Default "")
                        "status": 200, // Status code (Default 200)
                        "params": [  // The optional params to pass to the call (Default [])
                              "unique_request_identifier": "ROLE_ANONYMOUS"
                        ],
                  ]
            ]
      ],
];

```
### Step 2: Creating a new MockHandler instance and passing to the configuration array.
In order to create a new MockHandler instance, you should use `\OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath` and pass that instance into the configuration array with handler key, like so:
```php
$mockResponses = [...]; // As described above.
$mockHandler = \OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath($mockResponses);

$config = [/*the config*/];
$config['handler'] = $mockHandler;
$opencast = new \OpencastApi\Opencast($config);

```
#### Extra: Log requests uri
if you want to have a list of request uri, you can pass a file path variable as the second argument into the `\OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath` to write/append every uri into that file, like so;
```php
$filePath = '{A valid writeable file path}';
$mockResponses = [...]; // As described above.
$mockHandler = \OpencastApi\Mock\OcMockHanlder::getHandlerStackWithPath($mockResponses, $filePath);

$config = [/*the config*/];
$config['handler'] = $mockHandler;
$opencast = new \OpencastApi\Opencast($config);

```
# Naming convention
## Classes: 
Apart from 'Opencast' class, all other classes under `OpencastApi\Rest\` namespace start with `Oc` followed by the name and the endpoint category. For example:
- `OcEventsApi` contains 3 parts including Oc + Endpoint Name (Events) + Endpoint Category (Api)
- `OcServices` contains 2 parts including Oc + Endpoint Name/Category (Services)

## Opencast class properties:
The naming convention to access the endpoint subclasses from `OpencastApi\Opencast` as its properties, includes the name of the class without `Oc` in camelCase format. For example:
```php
use OpencastApi\Opencast;
$config = [/*the config*/];
$opencast = new Opencast($config);

// Accessing OcEventsApi would be like: (without Oc and in camelCase format)
$ocEventsApi = $opencast->eventsApi; 
```
# References
- <a href="https://develop.opencast.org/rest_docs.html" target="_blank">Main Opencast REST Service Documentation</a>
- <a href="https://docs.opencast.org/develop/developer/#api/#_top" target="_blank">Detailed Opencast REST API Endpoints Documentation</a>
