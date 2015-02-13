<?php
namespace Postmates;

class BaseDao extends \ArrayObject
{
    public function __construct($input=[], $flags=0, $iterator_class='ArrayIterator')
    {
        // Give the child a chance to map any of the values
        $mapped = $this->_map($input);
        parent::__construct($mapped, $flags, $iterator_class);
    }

    /**
     * Override to customize the $input before an ArrayObject is created.
     */
    protected function _map(array $input)
    {
        return $input;
    }

    /**
     * Postmates dates are all ISO8601 formatted.
     * This is a convenience method to hydrate a DateTime object for
     * a given string represenation in an API response.
     */
    static public function mapDateTime($sDate)
    {
        return date_create($sDate);
    }
}
