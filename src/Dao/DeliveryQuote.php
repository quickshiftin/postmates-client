<?php
namespace Postmates\Dao;

class DeliveryQuote extends \Postmates\BaseDao
{
    protected function _map(array $input)
    {
        // Map raw date times to objects
        $input['created']     = self::mapDateTime($input['created']);
        $input['expires']     = self::mapDateTime($input['expires']);
        $input['dropoff_eta'] = self::mapDateTime($input['dropoff_eta']);

        // Postmates passes the fee in cents
        $input['fee'] = $input['fee'] / 100;

        return $input;
    }
}
  
