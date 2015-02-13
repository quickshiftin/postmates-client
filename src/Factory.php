<?php
namespace Postmates;

class Factory
{
    /**
     * This is a factory method that will instantiate the appropriate PHP
     * object by inspecting the response payload.
     */
    static public function create(array $aJson)
    {
        // If no type was provided return the bare array
        if(!isset($aJson['type']))
            return $aJson;

        $sType = $aJson['type'];

        // Now try to hydrate a known object
        switch($sType) {
            case 'list':
                return new Dao\PList($aJson);
                break;
            case 'delivery_quote':
                return new Dao\DeliveryQuote($aJson);
                break;
            case 'delivery':
                return new Dao\Delivery($aJson);
                break;
            case 'error':
                return new Dao\Error($aJson);                
                break;
            default;
                throw new \UnexpectedValueException("Unsupported type $sType");
                break;
        }
    }
}
