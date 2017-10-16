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

use \Micro\Auth\Exception;
use \Micro\Auth\Adapter\AdapterInterface;
use \Micro\Auth\Identity;
use \Psr\Log\LoggerInterface as Logger;
use \Micro\Auth\AttributeMap;
use \Micro\Container\AdapterAwareInterface;

class Auth implements AdapterAwareInterface
{
    /**
     * Adapter
     *
     * @var array
     */
    protected $adapter = [];


    /**
     * Identity
     *
     * @var Identity
     */
    protected $identity;


    /**
     * Logger
     *
     * @var Logger
     */
    protected $logger;


    /**
     * Identity class
     *
     * @var string
     */
    protected $identity_class = Identity::class;


    /**
     * Attribute map class
     *
     * @var string
     */
    protected $attribute_map_class = AttributeMap::class;


    /**
     * Initialize
     *
     * @param   Logger $logger
     * @param   Iterable $config
     * @return  void
     */
    public function __construct(Logger $logger, ? Iterable $config = null)
    {
        $this->logger = $logger;
        $this->setOptions($config);
    }


    /**
     * Set options
     *
     * @param  Iterable $config
     * @return Auth
     */
    public function setOptions(? Iterable $config = null) : Auth
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'identity_class':
                case 'attribute_map_class':
                    $this->{$option} = (string)$value;
                break;

                case 'adapter':
                    foreach ($value as $name => $adapter) {
                        $this->injectAdapter($name, $adapter);
                    }
                break;
            }
        }

        return $this;
    }


    /**
     * Has adapter
     *
     * @param  string $name
     * @return bool
     */
    public function hasAdapter(string $name): bool
    {
        return isset($this->adapter[$name]);
    }


    /**
     * Inject adapter
     *
     * @param  string $name
     * @param  AdapterInterface $adapter
     * @return AdapterInterface
     */
    public function injectAdapter(string $name, AdapterInterface $adapter) : AdapterInterface
    {
        if ($this->hasAdapter($name)) {
            throw new Exception('auth adapter '.$name.' is already registered');
        }

        $this->adapter[$name] = $adapter;
        return $adapter;
    }


    /**
     * Get adapter
     *
     * @param  string $name
     * @return AdapterInterface
     */
    public function getAdapter(string $name): AdapterInterface
    {
        if (!$this->hasAdapter($name)) {
            throw new Exception('auth adapter '.$name.' is not registered');
        }

        return $this->adapter[$name];
    }


    /**
     * Get adapters
     *
     * @param  array $adapters
     * @return array
     */
    public function getAdapters(array $adapters = []): array
    {
        if (empty($adapter)) {
            return $this->adapter;
        } else {
            $list = [];
            foreach ($adapter as $name) {
                if (!$this->hasAdapter($name)) {
                    throw new Exception('auth adapter '.$name.' is not registered');
                }
                $list[$name] = $this->adapter[$name];
            }

            return $list;
        }
    }


    /**
     * Create identity
     *
     * @param  AdapterInterface $adapter
     * @return Identity
     */
    protected function createIdentity(AdapterInterface $adapter): Identity
    {
        $map = new $this->attribute_map_class($adapter->getAttributeMap(), $this->logger);
        $this->identity = new $this->identity_class($adapter, $map, $this->logger);
        return $this->identity;
    }


    /**
     * Authenticate
     *
     * @return  bool
     */
    public function requireOne(): bool
    {
        $result = false;

        foreach ($this->adapter as $name => $adapter) {
            try {
                if ($adapter->authenticate()) {
                    $this->createIdentity($adapter);

                    $this->logger->info("identity [{$this->identity->getIdentifier()}] authenticated over adapter [{$name}]", [
                        'category' => get_class($this)
                    ]);
                    $_SERVER['REMOTE_USER'] = $this->identity->getIdentifier();

                    return true;
                }
            } catch (\Exception $e) {
                $this->logger->error("failed authenticate user, unexcepted exception was thrown", [
                    'category' => get_class($this),
                    'exception'=> $e
                ]);
            }

            $this->logger->debug("auth adapter [{$name}] failed", [
                'category' => get_class($this)
            ]);
        }

        $this->logger->warning("all authentication adapter have failed", [
            'category' => get_class($this)
        ]);

        return false;
    }


    /**
     * Get identity
     *
     * @return Identity
     */
    public function getIdentity(): Identity
    {
        if (!$this->isAuthenticated()) {
            throw new Exception('no valid authentication yet');
        } else {
            return $this->identity;
        }
    }


    /**
     * Check if valid identity exists
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return ($this->identity instanceof Identity);
    }
}
