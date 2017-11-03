<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author      Raffael Sahli <sahli@gyselroth.net>
 * @copyright   Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license     MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter\Basic;

use \Micro\Auth\Exception;
use \Psr\Log\LoggerInterface;
use \Micro\Ldap as LdapServer;
use \Micro\Auth\Adapter\AdapterInterface;
use \Micro\Auth\Adapter\AbstractAdapter;

class Ldap extends AbstractBasic
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


    public function __construct(LdapServer $ldap, LoggerInterface $logger, ?Iterable $config=null)
    {
        $this->logger = $logger;
        $this->ldap = $ldap;
        $this->setOptions($config);
    }


    /**
     * Set options
     *
     * @param   Iterable $config
     * @return  AdapterInterface
     */
    public function setOptions(? Iterable $config = null) : AdapterInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'account_filter':
                    $this->account_filter = $value;
                    unset($config[$option]);
                break;
            }
        }

        parent::setOptions($config);

        return $this;
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
