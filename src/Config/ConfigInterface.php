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
     * Get from config
     *
     * @param   string $name
     * @return  mixed
     */
    public function __get(string $name);


    /**
     * Get native config format as config instance
     *
     * @return  Config
     */
    public function map($native = null): Config;
}
