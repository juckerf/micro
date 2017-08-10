<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2012-2017 gyselroth GmbH (https://gyselroth.com)
 * @license     GPLv3 https://opensource.org/licenses/GPL-3.0
 */

namespace Micro\Auth\Adapter\Basic;

use \Micro\Auth\Exception;
use \Psr\Log\LoggerInterface as Logger;
use \Micro\Ldap as LdapServer;
use \Micro\Auth\Adapter\AdapterInterface;
use \Micro\Auth\Adapter\AbstractAdapter;

class Ldap extends AbstractAdapter
{
    /**
     * Ldap
     *
     * @var LdapServer
     */
    protected $ldap;


    /**
     * LDAP DN
     *
     * @var string
     */
    protected $ldap_dn;


    /**
     * my account filter
     *
     * @var string
     */
    protected $account_filter = '(uid=%s)';
    

    /**
     * Ldap connect
     *
     * @param   Iterable $config
     * @param   Logger $logger
     * @return  void
     */
    public function __construct(?Iterable $config, Logger $logger)
    {
        $this->logger = $logger;
        $this->setOptions($config);
    }
    

    /**
     * Set options
     *
     * @param   Iterable $config
     * @return  AdapterInterface
     */
    public function setOptions(?Iterable $config=null): AdapterInterface
    {
        if ($config === null) {
            return $this;
        }
        
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'ldap':
                    $this->ldap = new LdapServer($value, $this->logger);
                break;
                
                case 'account_filter':
                    $this->account_filter = $value;
                break;
            }
        }
        
        if(!isset($config['ldap'])) {
            $this->ldap = new LdapServer();
        }

        return parent::setOptions($config);
    }
    

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
     * LDAP Auth
     *
     * @param   string $username
     * @param   string $password
     * @return  bool
     */
    protected function plainAuth(string $username, string $password): bool
    {
        $this->ldap->connect();
        $resource = $this->ldap->getResource();

        $esc_username = ldap_escape($username);
        $filter       = htmlspecialchars_decode(sprintf($this->account_filter, $esc_username));
        $result       = ldap_search($resource, $this->ldap->getBase(), $filter, ['dn']);
        $entries      = ldap_get_entries($resource, $result);

        if ($entries['count'] === 0) {
            $this->logger->warning("user not found with ldap filter [{$filter}]", [
                'category' => get_class($this)
            ]);

            return false;
        } elseif ($entries['count'] > 1) {
            $this->logger->warning("more than one user found with ldap filter [{$filter}]", [
                'category' => get_class($this)
            ]);

            return false;
        }

        $dn = $entries[0]['dn'];
        $this->logger->info("found ldap user [{$dn}] with filter [{$filter}]", [
            'category' => get_class($this)
        ]);

        $result = ldap_bind($resource, $dn, $password);
        $this->logger->info("bind ldap user [{$dn}]", [
            'category' => get_class($this),
            'result'   => $result
        ]);

        if ($result === false) {
            return false;
        }

        $this->identifier  = $username;
        $this->ldap_dn     = $dn;

        return true;
    }

    
    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $search = [];
        foreach ($this->map as $attr => $value) {
            $search[] = $value['attr'];
        }

        $result     = ldap_read($this->ldap->getResource(), $this->ldap_dn, '(objectClass=*)', $search);
        $entries    = ldap_get_entries($this->ldap->getResource(), $result);
        $attributes = $entries[0];

        $this->logger->info("get ldap user [{$this->ldap_dn}] attributes", [
            'category' => get_class($this),
            'params'   => $attributes,
        ]);

        return $attributes;
    }
}
