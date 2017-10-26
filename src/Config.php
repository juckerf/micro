<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro;

use \Micro\Config\Exception;
use \Micro\Config\ConfigInterface;
use \Iterator;
use \ArrayAccess;
use \Countable;
use \InvalidArgumentException;

class Config implements ArrayAccess, Iterator, Countable
{
    /**
     * Store
     *
     * @var array
     */
    protected $_store = [];


    /**
     * Position
     *
     * @var int
     */
    protected $position = 0;


    /**
     * Load config
     *
     * @param   string $config
     * @return  void
     */
    public function __construct(?ConfigInterface $config=null)
    {
        if($config !== null) {
            $this->config = $config->map();
        }
    }


    /**
     * Inject config adapter
     *
     * @param  ConfigInterface $config
     * @return Config
     */
    public function inject(ConfigInterface $config): Config
    {
        return $this->merge($config->map());
    }


    /**
     * Merge
     *
     * @return Config
     */
    public function merge(Config $from): Config
    {
        foreach($from as $key => $value) {
            if(isset($this->_store[$key]) && $this->_store[$key] instanceof Config) {
                $this->_store[$key]->merge($value);
            } else {
                $this->_store[$key] = $value;
            }
        }

        return $this;
    }


    /**
     * Count
     *
     * @return int
     */
    public function count()
    {
        return count($this->_store);
    }

    /**
     * Count
     *
     * @return int
     */
    public function children()
    {
        return $this->_store;
    }


    /**
     * Get entry
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_store[$key])) {
            return $this->_store[$key];
        } else {
            throw new Exception('requested config key '.$key.' is not available');
        }
    }


    /**
     * Set offset
     *
     * @param  int $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->_store[] = $value;
        } else {
            $this->_store[$offset] = $value;
        }
    }


    /**
     * Count
     *
     * @param  int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_store[$offset]);
    }


    /**
     * Unset offset
     *
     * @param  int $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_store[$offset]);
    }


    /**
     * Get value from offset
     *
     * @param  int $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->_store[$offset]) ? $this->_store[$offset] : null;
    }

    /**
     * Rewind
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->_store);
    }


    /**
     * Get current
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->_store);
    }


    /**
     * Get key
     *
     * @return int
     */
    public function key()
    {
        return key($this->_store);
    }


    /**
     * Next
     *
     * @return void
     */
    public function next()
    {
        next($this->_store);
    }


    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->_store) !== null;
    }
}
