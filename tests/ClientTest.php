<?php
use Postmates\Client;

class TestClient extends PHPUnit_Framework_TestCase
{
    const CUST_ID = 'cus_KCnit0JT3Pe6ak';
    const API_KEY = '0dbfacdb-10bd-4813-8042-c4f1bf3811a1';

    const WAREHOUSE_ADDRESS = '9601 E Iliff Ave Denver, CO 80231';
    const CUSTOMER_ADDRESS  = '2200 Market St, Denver, CO 80205';

    private
        $_oClient,
        $_oQuote,
        $_oDelivery,
        $_oDeliveryException,
        $_oDeliveries;

    protected function setUp()
    {
        // Construct the underlying Guzzle client
        $this->_oClient = new Client(['customer_id' => self::CUST_ID, 'api_key' => self::API_KEY]);

        // Fetch our deliveries
        $this->_oDeliveries = $this->_oClient->listDeliveries(Client::STATUS_DELIVERED);

        // Create a quote
        $this->_oQuote = $this->_oClient->requestDeliveryQuote(
            self::WAREHOUSE_ADDRESS, self::CUSTOMER_ADDRESS);

        // Create a delivery
        $this->_createDelivery();

        // Generate a request exception
        $this->_createDeliveryException();
    }

    // @TODO Test pagination ..
    public function testListDeliveries()
    {
        $this->assertInstanceOf('\\Postmates\\Dao\\PList', $this->_oDeliveries);
        $this->assertInstanceOf('\\Postmates\\Dao\\Delivery', $this->_oDeliveries[0]);

        // Verify our filter worked, the statuses should all be 'delivered'
        foreach($this->_oDeliveries as $oDelivery)
            $this->assertEquals($oDelivery['status'], Client::STATUS_DELIVERED);
    }

    /**
     * Request a quote and inspect the results.
     */
    public function testRequestDeliveryQuote()
    {
        $this->assertInstanceOf('\\Postmates\\Dao\\DeliveryQuote', $this->_oQuote);
        $this->assertInstanceOf('\\DateTime', $this->_oQuote['dropoff_eta']);
    }

    /**
     * Create a delivery.
     */
    public function testCreateDelivery()
    {
        $this->assertInstanceOf('\\Postmates\\Dao\\Delivery', $this->_oDelivery);
        $this->assertInstanceOf('\\DateTime', $this->_oDelivery['dropoff_eta']);
    }

    /**
    * Retrieve exceptions for a failed attempt to create a delivery.
    */
    public function testCreateDeliveryException()
    {
        $this->assertNull($this->_oDeliveryException);
        $exceptions = $this->_oClient->getRequestExceptions();
        foreach($exceptions as $e) {
            $this->assertInstanceOf('\\GuzzleHttp\\Exception\\RequestException', $e);
        }
    }

    /**
     * Get the status of a delivery.
     */
    public function testGetDeliveryStatus()
    {
        if(isset($this->_oDelivery['id']))
            $oDelivery = $this->_oClient->getDeliveryStatus($this->_oDelivery['id']);
        else
            $oDelivery = $this->_oClient->getDeliveryStatus($this->_oDeliveries[0]['id']);

        $this->assertInstanceOf('\\Postmates\\Dao\\Delivery', $oDelivery);
    }

    /**
     */
    public function testCancelDelivery()
    {
        $oDelivery = $this->_oClient->cancelDelivery($this->_oDelivery['id']);
        $this->assertInstanceOf('\\Postmates\\Dao\\Delivery', $oDelivery);
        $this->assertEquals($oDelivery['status'], Client::STATUS_CANCELED);
    }

    public function testReturnDelivery()
    {
        // Look for a delivery that's already been picked up, but not dropped of
        $oDeliveries = $this->_oClient->listDeliveries(Client::STATUS_PICKUP_COMPLETE);
        if(count($oDeliveries) < 1)
            return;

        $oDelivery        = $oDeliveries[0];
        $oReverseDelivery = $this->_oClient->returnDelivery($oDelivery['id']);

        $this->assertEquals($oDelivery['pickup']['address'], $oReverseDelivery['dropoff']['address']);
    }

    private function _createDelivery()
    {
        $this->_oDelivery = $this->_oClient->createDelivery(
            'A bag of groceries',
            'Instamart Warehouse',
            self::WAREHOUSE_ADDRESS,
            '800-555-1234',
            'Roy Rogers',
            self::CUSTOMER_ADDRESS,
            '303-425-8803',
            '',
            '',
            'Instamart Online Grocery Market',
            'Say hi to the customer for us!',
            $this->_oQuote['id']
        );
    }

    private function _createDeliveryException()
    {
        $this->_oDeliveryException = $this->_oClient->createDelivery(
            'A bag of groceries',
            'Instamart Warehouse',
            'bad_address', // this will generate an exception because Postmates cannot geocode it
            '800-555-1234',
            'Roy Rogers',
            self::CUSTOMER_ADDRESS,
            '303-425-8803',
            '',
            '',
            'Instamart Online Grocery Market',
            'Say hi to the customer for us!',
            $this->_oQuote['id']
        );
    }
}
