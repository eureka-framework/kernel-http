<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Framework\Kernel\Controller;

/**
 * Data Collection class.
 *
 * @author Romain Cottard
 */
class DataCollection implements \Iterator
{
    /** @var integer $length Length of the collection */
    protected $length = 0;

    /** @var integer Current position of the cursor in collection. */
    protected $index = 0;

    /** @var array $indices Index of keys */
    protected $indices = array();

    /** @var array $collection Collection of data. */
    protected $collection = array();

    /**
     * DataCollection constructor.
     */
    public function __construct()
    {
        $this->collection = array();
    }

    /**
     * Add data to the collection.
     *
     * @param  string $key
     * @param  mixed $value
     * @return $this
     */
    public function add($key, $value)
    {
        $this->collection[$key]       = $value;
        $this->indices[$this->length] = $key;
        $this->length++;

        return $this;
    }

    /**
     * Get length of the collection.
     *
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * Get current data
     *
     * @return mixed
     */
    public function current()
    {
        return $this->collection[$this->indices[$this->index]];
    }

    /**
     * Reset internal cursor.
     *
     * @return void
     */
    public function reset()
    {
        $this->index = 0;
    }

    /**
     * Get current key.
     *
     * @return string
     */
    public function key()
    {
        return $this->indices[$this->index];
    }

    /**
     * Go to the next data
     *
     * @return void
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * Go to the previous data.
     *
     * @return void
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * Check if have more data in the collection
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->index < $this->length);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }
}
