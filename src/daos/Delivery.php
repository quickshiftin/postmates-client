<?php
namespace Postmates\Dao;

class Delivery extends \Postmates\BaseDao
{
    protected function _map(array $input)
    {
        // Map raw date times to objects
        $input['created']          = self::mapDateTime($input['created']);
        $input['updated']          = self::mapDateTime($input['updated']);
        $input['pickup_eta']       = self::mapDateTime($input['pickup_eta']);
        $input['dropoff_eta']      = self::mapDateTime($input['dropoff_eta']);
        $input['dropoff_deadline'] = self::mapDateTime($input['dropoff_deadline']);

        return $input;
    }
}