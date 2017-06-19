<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @copyright Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 */

namespace Micro\Config;

use \Micro\Config;

interface ConfigInterface
{
    /**
     * Load
     *
     * @param  string $config
     * @param  string $env
     * @return void
     */
    public function __construct(string $config, string $env);


    /**
     * Get entire simplexml
     *
     * @return mixed
     */
    public function getRaw();

    
    /**
     * Get from config
     *
     * @param   string $name
     * @return  mixed
     */
    public function __get(string $name);


    /**
     * Add config tree and merge it
     *
     * @param   mixed $config
     * @return  ConfigInterface
     */
    public function merge($config);


    /**
     * Get native config format as config instance
     *
     * @param   mixed $config
     * @return  Config
     */
    public function map($native=null): Config;
}
