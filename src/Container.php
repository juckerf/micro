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

use \ReflectionClass;
use \Closure;
use \Micro\Container\Exception;
use \Micro\Container\AdapterAwareInterface;

class Container
{
    /**
     * Config
     *
     * @var array
     */
    protected $config;


    /**
     * Service registry
     *
     * @var array
     */
    protected $service = [];


    /**
     * Registered but not initialized service registry
     *
     * @var array
     */
    protected $registry = [];


    /**
     * Create container
     *
     * @param array $config
     */
    public function __construct(array $config=[])
    {
        $this->config = $config;
    }


    /**
     * Get service
     *
     * @param  string $name
     * @return mixed
     */
    public function get(string $name)
    {
        if($this->has($name)) {
            return $this->service[$name];
        } else {
            if(isset($this->registry[$name])) {
                $this->service[$name] = $this->registry[$name]->call($this);
                unset($this->registry[$name]);
                return $this->service[$name];
            } else {
                return $this->service[$name] = $this->autoWire($name);
            }
        }
    }


    /**
     * Add service
     *
     * @param  string $name
     * @param  Closure $service
     * @return Container
     */
    public function add(string $name, Closure $service): Container
    {
        if($this->has($name)) {
            throw new Exception('service '.$name.' is already registered');
        }

        $this->registry[$name] = $service;
        return $this;
    }


    /**
     * Check if service is registered
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->service[$name]);
    }


    /**
     * Auto wire service
     *
     * @param  string $name
     * @return mixed
     */
    protected function autoWire(string $name)
    {
        if(isset($this->config[$name]) && isset($this->config[$name]['class'])) {
            $class = $this->config[$name]['class'];
        } else {
            $class = $name;
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if($constructor === null) {
            return new $class();
        } else {
            $params = $constructor->getParameters();
            $args = [];

            foreach($params as $param) {
                $type = $param->getClass();
                $param_name = $param->getName();

                if($type === null) {
                    try {
                        $args[$param_name] = $this->getParam($name, $param_name);
                    } catch(Exception $e) {
                        if($param->isDefaultValueAvailable()) {
                            $args[$param_name] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }
                } else {
                    $type_class = $type->getName();
                    $args[$type_class] = $this->get($type_class);
                }
            }

            return $this->createInstance($name, $reflection, $args);
        }
    }


    /**
     * Create instance
     *
     * @param  string $name
     * @param  ReflectionClass $class
     * @param  array $args
     * @return mixed
     */
    protected function createInstance(string $name, ReflectionClass $class, array $args)
    {
        $instance = $class->newInstanceArgs($args);
        if($instance instanceof AdapterAwareInterface) {
            if(isset($this->config[$name]) && isset($this->config[$name]['adapter'])) {
                foreach($this->config[$name]['adapter'] as $adapter => $config) {
                    if(!isset($config['class'])) {
                        throw new Exception('adapter requires class configuration');
                    }

                    if(isset($config['enabled']) && $config['enabled'] === '0') {
                        continue;
                    }

                    $adapter_instance = $this->get($config['class']);
                    $instance->injectAdapter($adapter, $adapter_instance);
                }
            }
        }

        return $instance;
    }


    /**
     * Get config value
     *
     * @param  string $name
     * @param  string $param
     * @return mixed
     */
    public function getParam(string $name, string $param)
    {
        if(!isset($this->config[$name]) && !isset($this->config[$name]['options'])) {
            throw new Exception('no configuration available for service '.$name);
        }

        if(!isset($this->config[$name]['options'][$param])) {
            throw new Exception('no configuration available for service parameter '.$param);
        }

        return $this->config[$name]['options'][$param];
    }
}
