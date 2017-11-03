<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter\Basic;

use \Micro\Auth\Exception;
use \Psr\Log\LoggerInterface as Logger;
use \Micro\Auth\Adapter\AdapterInterface;
use \Micro\Auth\Adapter\AbstractAdapter;

abstract class AbstractBasic extends AbstractAdapter
{
    /**
     * Attributes
     *
     * @var array
     */
    protected $attributes = [];


    /**
     * Authenticate
     *
     * @return bool
     */
    public function authenticate(): bool
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->logger->debug('skip auth adapter ['.get_class($this).'], no http authorization header found', [
                'category' => get_class($this)
            ]);

            return false;
        }

        $header = $_SERVER['HTTP_AUTHORIZATION'];
        $parts  = explode(' ', $header);

        if ($parts[0] == 'Basic') {
            $this->logger->debug('found http basic authorization header', [
                'category' => get_class($this)
            ]);

            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];

            return $this->plainAuth($username, $password);
        } else {
            $this->logger->warning('http authorization header contains no basic string or invalid authentication string', [
                'category' => get_class($this)
            ]);

            return false;
        }
    }


    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
