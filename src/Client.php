<?php
namespace Postmates;

class Client extends GuzzleHttpClient
{
    static private $_aStatuses = [
        200 => 'OK: Everything went as planned.',
        304 => 'Not Modified: Resource hasn\'t been updated since the date provided. See Caching below.',
        400 => 'Bad Request: You did something wrong. Often a missing argument or parameter.',
        401 => 'Unauthorized: Authentication was incorrect.',
        404 => 'Not Found',
        500 => 'Internal Server Error: We had a problem processing the request.',
        503 => 'Service Unavailable: Try again later.'
    ];

    private $_sCustomerId;

    public function __construct(array $config=[])
    {
        // Validate Postmates config values, these are required for the Postmates Client
        if(!isset($config['customer-id']))
            throw new \InvalidArgumentException('Missing the Postmates Customer ID');
        if(!isset($config['api-key']))
            throw new \InvalidArgumentException('Missing the Postmates API Key');

        // Optional Postmates version
        $aHeaders = [];
        if(isset($config['postmates-version']))
            $aHeaders = ['X-Postmates-Version' => $config['postmates-version']];

        // Store the customer id on the instance for URI generation
        $this->_sCustomerId = $config['customer_id'];

        // Construct the underlying Guzzle client
        parent::__construct(
            ['base_url' =>
            ['https://api.postmates.com/{version}/', ['version' => 'v1']],
            'defaults' => [
                'headers' => $aHeaders,
                // HTTP Basic auth header, username is api key, password is blank
                'auth'    => [$config['api_key'], ''],
            ]]);
    }

    /**
     * The first step in using the Postmates API is get a quote on a delivery.
     * This allows you to make decisions about the appropriate cost and availability
     * for using the postmates platform, which can vary based on distance and demand.
     *
     * A Delivery Quote is only valid for a limited duration. After which, referencing
     * the quote while creating a delivery will not be allowed.
     *
     * You'll receive a DeliveryQuote response.
     */
    public function requestDeliveryQuote($sPickupAddress, $sDropoffAddress)
    {
        $oRq = $this->post(
            "/customers/{$this->_sCustomerId}/delivery_quotes", [],
            ['pickup_address' => $sPickupAddress, 'dropoff_address' => $sDropoffAddress]);

        return $this->_request($oRq);
    }

    /**
     * Once a delivery is accepted, the delivery fee will be deducted from your account.
     * Providing the previously generated quote id is optional, but ensures the costs
     * and etas are consistent with the quote.
     */
    public function createDelivery(
        $sManifest,
        $sPickupName,
        $sPickupAddress,
        $sPickupPhoneNumber,
        $sDropoffName,
        $sDropoffBusinessName='',
        $sManifestReference='',
        $sPickupBusinessName='',
        $sPickupNotes='',
        $sDropoffNotes='',
        $iQuoteId=null
    ) {
        // Add the required arguments
        $aRq = [
            'manifest'            => $sManifest,
            'pickup_name'         => $sPickupName,
            'pickup_address'      => $sPickupAddress,
            'pickup_phone_number' => $sPickupPhoneNumber,
            'dropoff_name'        => $sDropoffName
        ];

        // Add optional arguments
        if(!empty($sDropffBusinessName))
            $aRq['dropoff_business_name'] = $sDropoffBusinessName;
        if(!empty($sManifestReference))
            $aRq['manifest_reference'] = $sManifestReference;
        if(!empty($sPickupBusinessName))
            $aRq['pickup_business_name'] = $sPickupBusinessName;
        if(!empty($sPickupNotes))
            $aRq['pickup_notes'] = $sPickupNotes;
        if($iQuoteId !== null)
            $aRq['quote_id'] = $iQuoteId;

        // Configure and send the request
        $oRq = $this->post("/customers/{$this->_sCustomerId}/deliveries", [], $aRq);
        return $this->_request($oRq);
    }

    /**
     * List all deliveries for a customer. Only ongoing deliveries are retuned by default
     * (a feature of the PHP client). Pass false as the first argument to see all deliveries.
     */
    public function listDeliveries($bOnlyOngoing=true)
    {
        $aOptions = [];
        if($bOnlyOngoing)
            $aOptions['filter'] = 'ongoing';

        $oRq = $this->get("/customers/{$this->_sCustomerId}/deliveries", [], $aOptions);

        return $this->_request($oRq);
    }

    /**
     * Retrieve updated details about a delivery.
     * Returns: Delivery Object
     */
    public function getDeliveryStatus($iDeliveryId)
    {
        $oRq = $this->get("/customers/{$this->_sCustomerId}/deliveries/{$iDeliveryId}");
        return $this->_request($oRq);
    }

    /**
     * Cancel an ongoing delivery.
     * Returns: Delivery Object
     * A delivery can only be canceled prior to a courier completing pickup. Delivery fees still apply.
     */
    public function cancelDelivery($iDeliveryId)
    {
        $oRq = $this->get("/customers/{$this->_sCustomerId}/deliveries/{$iDeliveryId}/cancel");
        return $this->_request($oRq);
    }

    /**
     * Cancel an ongoing delivery that was already picked up
     * and create a delivery that is a reverse of the original.
     * The items will get returned to the original pickup location.
     *
     * A delivery can only be reversed once the courier completed pickup and before the
     * courier has completed dropoff. Delivery fees apply to both the cancelled delivery
     * and new returned delivery.
     *
     * Returns: Delivery Object (the new return delivery)
     */
    public function returnDelivery($iDeliveryId)
    {
        $oRq = $this->get("/customers/{$this->_sCustomerId}/deliveries/{$iDeliveryId}/return");
        return $this->_request($oRq);
    }

    /**
     * Trap for HTTP Exceptions.
     * XXX Handling for this needs to be configurable.
     * Convert responses to JSON and return an appropriate hydrated data object.
     */
    private function _request($oRq)
    {
        try {
            $oRsp = $oRq->send();
            return $this->_response($oRsp->json());;
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            echo 'Uh oh! ' . $e->getMessage();
            echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . "\n";
            echo 'HTTP request: ' . $e->getRequest() . "\n";
            echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . "\n";
            echo 'HTTP response: ' . $e->getResponse() . "\n";
        }
    }

    /**
     * This is a factory method that will instantiate the appropriate PHP
     * object by inspecting the response payload.
     */
    private function _response($sJson)
    {
        $aJson = json_decode($sJson, true);

        // If no type was provided return the bare array
        if(!isset($aJson['type']))
            return $aJson;

        // Now try to hydrate a known object
        switch($aJson['type']) {
            case 'list':
                return new PList($aJson);
                break;
            case 'delivery_quote':
                return new DeliveryQuote($aJson);
                break;
            case 'delivery':
                return new Delivery($aJson);
                break;
        }
    }
}