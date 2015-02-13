<?php

namespace Postmates;

class ListResponse extends \ArrayObject
{
    public function getTotalCount()
    {
        if(!isset($this['total_count']))
            return 1;
        return $this['total_count'];
    }

    public function getNextHref()
    {
        if(!isset($this['next_href']))
            return null;
        return $this['next_href'];
    }

    public function getData()
    {
        return $this['data'];
    }
}