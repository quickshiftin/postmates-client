# postmates-client
An API client for [Postmates](https://postmates.com/developer) on demand logistics

You can find the Postmates documentation [here](https://postmates.com/developer/docs).

The Postmates API is RESTful, so this client library extends the [`\Guzzlehttp\Client`](http://guzzle.readthedocs.org/en/latest/).

## Authentication
You instantiate `\Postmates\Client` the same as you would `\Guzzlehttp\Client` except there are 2 new required configuration options and one new optional configuration option. The new required options are `customer_id` and `api_key` which you get once you [register](https://postmates.com/developer/register) your app. There's also an optional configuration option `postmates_version` which you can use to ensure consistent fields. Instantiating the client then looks like so
```php
$oClient = new Client(['customer_id' => $cust_id, 'api_key' => $api_key]);
```
Where `$cust_id` and `$api_key` are your respective credentials.

## API Methods
All the API methods have become public member functions of the \Postmates\Client class.

### Request Delivery Quote
```php
// $oQuote is an instance of \Postmates\Dao\DeliveryQuote
$oQuote = $this->_oClient->requestDeliveryQuote($sPickupAddress, $sDropoffAddress);

// You may access a value from the JSON using array notation.
// Also remember the timestamps have been converted to \DateTime instances for us.
$oDropoffEta = $oQuote['dropoff_eta'];
echo 'Dropoff ETA: ' . $oDropoffEta->format("h:i a\n");
```

### Create a Delivery
```php
// $oDelivery is an instance of \Postmates\Dao\Delivery
$oDelivery = $oClient->createDelivery(
    /* Required arguments */
    $sManifest,
    $sPickupName,
    $sPickupAddress,
    $sDropoffName,
    $sDropoffAddress,
    $sDropoffPhoneNumber,

    /* Optional arguments */   
    $sDropoffBusinessName='',
    $sManifestReference='',
    $sPickupBusinessName='',
    $sPickupNotes='',
    $sDropoffNotes='',
    $iQuoteId=null // @hint You can pass the id of a quote as $oQuote['id']
);
```

### List Deliveries
When listing deliveries you may filter by one of the order statuses, *pending*, *pickup*, *pickup_complete*, *dropoff*, *canceled*, *delivered*, *returned*. There are more details on the meanings of each status in the code and on the Postmates API documentation.
```php
// Get a list of all Deliveries
// $oDeliveries is an instance of \Postmates\Dao\PList
// Assuming there is at least one Delivery in the response,
// $oDeliveries[0] is an instance of \Postmates\Dao\Delivery
$oDeliveries = $oClient->listDeliveries();

// Get a list of *pickup_complete* Deliveries
$oDelivereies = $oClient->listDeliveries(\Postmates\Client::STATUS_PICKUP_COMPLETE);

```

### Get Delivery Status
```php
// Just pass the id of a delivery and you'll get back a \Postmates\Dao\Delivery.
$oDelivery = $oClient->getDeliveryStatus($sDeliveryId);
```

### Cancel a Delivery
A delivery can only be canceled prior to a courier completing pickup, which means the status must be either *pending* or *pickup*.
```php
$oDelivery = $oClient->cancelDelivery($iDeliveryId);
```

### Return a Delivery
A delivery can only be reversed once the courier completed pickup and before the courier has completed dropoff. This means the status can only be *pickup_complete*.
```php
$oDelivery = $oClient->returnDelivery($iDeliveryId);
```

## Client Library Data Objects
The Postmates client handily converts response JSON objects from the API into objects that subclass \ArrayObject. As a matter of convenience the client library also converts textual timestamps from the response to \DateTime instances.

## TODO
 * Pagination support and testing
 * Configurable Exception handling
 * Configurable Dao classes
 * Optional Dao instead of id for client library methods
   * EG $oDelivery = $oClient->createDelivery(); $oClient->cancelDelivery($oDelivery);
