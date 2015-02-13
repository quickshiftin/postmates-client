<?php
namespace Postmates\Dao;

class PList extends \Postmates\BaseDao
{
    protected function _map(array $input)
    {
        // Map all the children in the list
        $_aInput = [];
        foreach($input as $_aObject)
            $_aInput[] = \Postmates\Factory::create($_aObject);

        return $input;
    }
}