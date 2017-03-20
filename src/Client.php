<?php
namespace Postmates;

class Client extends \GuzzleHttp\Client
{
    const STATUS_PENDING         = 'pending';         // We've accepted the delivery and will be assigning it to a courier.
    const STATUS_PICKUP          = 'pickup';          // Courier is assigned and is en route to pick up the items
    const STATUS_PICKUP_COMPLETE = 'pickup_complete'; // Courier has picked up the items
    const STATUS_DROPOFF         = 'dropoff';         // Courier is moving towards the dropoff
    const STATUS_CANCELED        = 'canceled';        // Items won't be delivered. Deliveries are either canceled by the customer or by our customer service team.
    const STATUS_DELIVERED       = 'delivered';       // Items were delivered successfully.
    const STATUS_RETURNED        = 'returned';        // The delivery was canceled and a new job created to return items to sender. (See related_deliveries in delivery object.)

    static private $_aValidStatuses = [
        self::STATUS_PENDING, self::STATUS_PICKUP, self::STATUS_PICKUP_COMPLETE, self::STATUS_DROPOFF,
        self::STATUS_CANCELED, self::STATUS_DELIVERED, self::STATUS_RETURNED
    ];

    private
        $_sCustomerId,
        $requestExceptions;

    public function __construct(array $config=[])
    {
        // Validate Postmates config values, these are required for the Postmates Client
        if(!isset($config['customer_id'])) {
            throw new \InvalidArgumentException('Missing the Postmates Customer ID');
        }

        if(!isset($config['api_key'])) {
            throw new \InvalidArgumentException('Missing the Postmates API Key');
        }

        // Optional Postmates version
        $aHeaders = [];
        if(isset($config['postmates_version'])) {
            $aHeaders = ['X-Postmates-Version' => $config['postmates_version']];
        }

        // Store the customer id on the instance for URI generation
        $this->_sCustomerId = $config['customer_id'];

        // Construct the underlying Guzzle client
        parent::__construct([
            'base_uri' => 'https://api.postmates.com/v1/',
            'headers'  => $aHeaders,
            'auth'     => [$config['api_key'], ''],
        ]);
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
        $type     = 'POST';
        $endpoint = 'customers/' . $this->_sCustomerId . '/delivery_quotes';
        $params   = [
            'form_params' => [
                   'pickup_address'  => $sPickupAddress,
                   'dropoff_address' => $sDropoffAddress
               ]
            ];

        return $this->_request($type, $endpoint, $params);
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
        $sDropoffAddress,
        $sDropoffPhoneNumber,
        $sDropoffBusinessName='',
        $sManifestReference='',
        $sPickupBusinessName='',
        $sPickupNotes='',
        $sDropoffNotes='',
        $iQuoteId=null
    ) {
        // Add the required arguments
        $aRq = [
            'manifest'             => $sManifest,
            'pickup_name'          => $sPickupName,
            'pickup_address'       => $sPickupAddress,
            'pickup_phone_number'  => $sPickupPhoneNumber,
            'dropoff_name'         => $sDropoffName,
            'dropoff_address'      => $sDropoffAddress,
            'dropoff_phone_number' => $sDropoffPhoneNumber,
        ];

        // Add optional arguments
        if(!empty($sDropffBusinessName)) {
            $aRq['dropoff_business_name'] = $sDropoffBusinessName;
        }

        if(!empty($sManifestReference)) {
            $aRq['manifest_reference'] = $sManifestReference;
        }

        if(!empty($sPickupBusinessName)) {
            $aRq['pickup_business_name'] = $sPickupBusinessName;
        }

        if(!empty($sPickupNotes)) {
            $aRq['pickup_notes'] = $sPickupNotes;
        }

        if(!empty($sDropoffNotes)) {
            $aRq['dropoff_notes'] = $sDropoffNotes;
        }

        if($iQuoteId !== null) {
            $aRq['quote_id'] = $iQuoteId;
        }

        $type     = 'POST';
        $endpoint = 'customers/'. $this->_sCustomerId . '/deliveries';
        $params   = [
            'form_params' => $aRq
        ];

        return $this->_request($type, $endpoint, $params);
    }

    /**
     * List all deliveries for a customer optionally restricted by a provided status.
     */
    public function listDeliveries($sStatusFilter='')
    {
        $aOptions = [];
        if($sStatusFilter != '' && in_array($sStatusFilter, self::$_aValidStatuses)) {
            $aOptions['filter'] = $sStatusFilter;
        }

        $type     = 'GET';
        $endpoint = 'customers/'. $this->_sCustomerId . '/deliveries';
        $params   = ['query' => $aOptions];

        return $this->_request($type, $endpoint, $params);
    }

    /**
     * Retrieve updated details about a delivery.
     * Returns: Delivery Object
     */
    public function getDeliveryStatus($iDeliveryId)
    {
        $type     = 'GET';
        $endpoint = 'customers/' . $this->_sCustomerId . '/deliveries/' . $iDeliveryId;

        return $this->_request($type, $endpoint);
    }

    /**
     * Cancel an ongoing delivery.
     * Returns: Delivery Object
     * A delivery can only be canceled prior to a courier completing pickup. Delivery fees still apply.
     */
    public function cancelDelivery($iDeliveryId)
    {
        $type     = 'POST';
        $endpoint = 'customers/' . $this->_sCustomerId . '/deliveries/' . $iDeliveryId . '/cancel';

        return $this->_request($type, $endpoint);
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
        $type     = 'POST';
        $endpoint = 'customers/'. $this->_sCustomerId . '/deliveries/' . $iDeliveryId . '/return';

        return $this->_request($type, $endpoint);
    }

    /**
     * Trap for HTTP Exceptions.
     * XXX Handling for this needs to be configurable.
     * XXX More specific handling than just \Exception ...
     * Convert responses to JSON and return an appropriate hydrated data object.
     */
    private function _request($type, $endpoint, $params = [])
    {
        try {
            $oRq = $this->request($type, $endpoint, $params);
            $oRsp = $this->send($oRq);

            return Factory::create($oRsp->json());
        } catch(\GuzzleHttp\Exception\RequestException $e) {
            $this->addException($e);
        } catch(\Exception $e) {
            $this->addException($e);
        }
    }

    private function addException($e)
    {
        $this->requestExceptions[] = $e;
    }

    /**
     * Retrieve an array of exceptions, if any were thrown during the request.
     * See http://docs.guzzlephp.org/ for methods available with Guzzle request exceptions.
     */
    public function getRequestExceptions()
    {
        return $this->requestExceptions;
    }
}
