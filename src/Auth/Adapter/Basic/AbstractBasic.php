<?php
declare(strict_types = 1);

/**
 * Micro
 *
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
     * Auth
     *
     * @param   string $username
     * @param   string $password
     * @return  bool
     */
    protected function plainAuth(string $username, string $password): bool
    {
        $result = $this->findIdentity($username);

        if ($result === null) {
            $this->logger->info('found no user named ['.$username.'] in database', [
                'category' => get_class($this)
            ]);

            return false;
        }
        
        if (!isset($result['password']) || empty($result['password'])) {
            $this->logger->info('found no password for ['.$username.'] in database', [
                'category' => get_class($this)
            ]);
         
            return false;
        }

        if (!password_verify($password, $result['password'])) {
            $this->logger->info('failed match given password for ['.$username.'] with stored hash in database', [
                'category' => get_class($this)
            ]);
         
            return false;
        }

        $this->identifier = $username;
        return true;
    }


    /**
     * Find Identity
     *
     * @param  string $username
     * @return array
     */
    protected abstract function findIdentity(string $username): ? array;
}
