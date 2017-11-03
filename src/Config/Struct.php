<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Config;

use \Micro\Config;

class Struct implements ConfigInterface
{
    /**
     * Store
     *
     * @var Config
     */
    private $store;


    /**
     * Load config
     *
     * @param  array $config
     */
    public function __construct(array $config)
    {
        $this->store = $config;
    }


    /**
     * Get from config
     *
     * @param   string $name
     * @return  mixed
     */
    public function __get(string $name)
    {
        return $this->store[$name];
    }


    /**
     * Return map
     *
     * @return  Config
     */
    public function map(): Config
    {
        return $this->mapArray($this->store);
    }


    /**
     * map Array
     *
     * @param   array $config
     * @return  Config
     */
    protected function mapArray(array $array): Config
    {
        $config = new Config();
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $config[$key] = $this->mapArray($value);
            } else {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
