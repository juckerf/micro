<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro;

use \Micro\Ldap\Exception;
use \Psr\Log\LoggerInterface as Logger;

class Ldap
{
    /**
     * Connection resource
     *
     * @var resource
     */
    protected $connection;


    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * URI
     *
     * @var string
     */
    protected $uri = 'ldap://127.0.0.1:389';


    /**
     * Binddn
     *
     * @var string
     */
    protected $binddn;


    /**
     * Bindpw
     *
     * @var string
     */
    protected $bindpw;
    

    /**
     * Basedn
     *
     * @var string
     */
    protected $basedn = '';


    /**
     * tls
     *
     * @var bool
     */
    protected $tls = false;


    /**
     *  Options
     *
     * @var array
     */
    protected $options = [];


    /**
     * construct
     *
     * @param   Iterable $config
     * @param   Logger $logger
     * @return  resource
     */
    public function __construct(? Iterable $config, Logger $logger)
    {
        $this->setOptions($config);
        $this->logger = $logger;
    }


    /**
     * Connect
     *
     * @return Ldap
     */
    public function connect(): Ldap
    {
        if ($this->binddn === null) {
            $this->logger->warning('no binddn set for ldap connection, you should avoid anonymous bind', [
                'category' => get_class($this),
            ]);
        }
        
        if ($this->tls === false && substr($this->uri, 0, 5) !== 'ldaps') {
            $this->logger->warning('neither tls nor ldaps enabled for ldap connection, it is strongly reccommended to encrypt ldap connections', [
                'category' => get_class($this),
            ]);
        }

        $this->connection = ldap_connect($this->uri);

        if ($this->tls === true) {
            ldap_start_tls($this->connection);
        }

        foreach ($this->options as $opt => $value) {
            ldap_set_option($this->connection, constant($value['attr']), $value['value']);
        }

        if ($this->connection) {
            if ($this->binddn !== null) {
                $bind = ldap_bind($this->connection, $this->binddn, $this->bindpw);

                if ($bind) {
                    $this->logger->info('bind to ldap server ['.$this->uri.'] with binddn ['.$this->binddn.'] was succesful', [
                        'category' => get_class($this),
                    ]);

                    return $this;
                } else {
                    throw new Exception('failed bind to ldap server, error: '.ldap_error($this->connection));
                }
            }
        } else {
            throw new Exception('failed connect to ldap server '.$this->uri);
        }

        return $this;
    }


    /**
     * Close socket
     *
     * @return bool
     */
    public function close(): bool
    {
        if (is_resource($this->connection)) {
            return ldap_unbind($this->connection);
        }

        return true;
    }

    
    /**
     * Set options
     *
     * @param  Iterable $config
     * @return Ldap
     */
    public function setOptions(? Iterable $config = null) : Ldap
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'uri':
                    $this->uri = (string)$value;
                    break;
                case 'options':
                    $this->options = $value;
                    break;
                case 'username':
                    $this->binddn = (string)$value;
                    break;
                case 'password':
                    $this->bindpw = (string)$value;
                    break;
                case 'basedn':
                    $this->basedn = (string)$value;
                    break;
                case 'tls':
                    $this->tls = (bool)(int)$value;
                    break;
            }
        }
    
        return $this;
    }

    
    /**
     * Get base
     *
     * @return string
     */
    public function getBase(): string
    {
        return $this->basedn;
    }


    /**
     * Get connection
     *
     * @return resource
     */
    public function getResource()
    {
        if (!is_resource($this->connection)) {
            $this->connect();
        }
    
        return $this->connection;
    }
}
