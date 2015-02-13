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
        // Seems Postmates sometimes uses the key 'object' when
        // they pass a list kind, although the docs say it will be in kind...
        if(isset($aJson['object']) && $aJson['object'] == 'list')
            return new Dao\PList($aJson);

        // Now try to hydrate a known object
        $sKind = $aJson['kind'];
        switch($sKind) {
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
                throw new \UnexpectedValueException("Unsupported type $sKind");
                break;
        }

        // If no type was provided return the bare array
        return $aJson;
    }
}
