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
use \Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * Config
     *
     * @var array
     */
    protected $config = [];


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
        $this->flattenConfig($config);
        $container = $this;
        $this->add(ContainerInterface::class, function() use($container){
            return $container;
        });
    }


    /**
     * Flatten config
     *
     * @param  Iterable $config
     * @param  string $parent
     * @return array
     */
    protected function flattenConfig(Iterable $config, ?string $parent=null): array
    {
        $flat = [];
        foreach($config as $name => $service) {
            if(isset($service['name']) && $parent === null) {
                $id = $service['name'];
           } else {
                if($parent === null) {
                    $id = $name;
                } else {
                    $id = $parent.'.'.$name;
                }
            }

            $flat[$id] = [
                'name' => $name
            ];

            foreach($service as $option => $value) {
                switch($option) {

                    case 'name' :
                    case 'use' :
                        $flat[$id][$option] = $value;
                    break;

                    case 'options':
                        $flat[$id][$option] = (array)$value;
                    break;

                    case 'service':
                        $flat[$id]['parent'] = $parent;
                        $parent = $parent.'.'.$name;
                        $services = $this->flattenConfig($service['service'], $parent);
                        $flat[$id]['service'] = [];
                        foreach($services as $key => $sub) {
                            $flat[$id]['service'][$sub['name']] = $key;
                        }
                    break;

                    case 'adapter':
                        $flat[$id]['adapter'] = $this->flattenConfig($service['adapter']);
                    break;

                    default:
                        throw new Exception('invalid container configuration '.$option.' given');
                }
            }
        }

        $this->config = array_merge($this->config, $flat);
        return $flat;
    }


    /**
     * Get all services
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->service;
    }


    /**
     * Get service
     *
     * @param  string $name
     * @return mixed
     */
    public function get($name)
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
     * Get new instance (Do not store in container)
     *
     * @param  string $name
     * @return mixed
     */
    public function getNew(string $name)
    {
        if(isset($this->registry[$name])) {
            return $this->registry[$name]->call($this);
        } else {
            return $this->autoWire($name);
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
    public function has($name): bool
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
        if(isset($this->config[$name]['use'])) {
            $class = $this->config[$name]['use'];
        } elseif(isset($this->config[$name]['name'])) {
            $class = $this->config[$name]['name'];
        } else {
            $class = $name;
        }

        try {
            $reflection = new ReflectionClass($class);
        } catch(\Exception $e) {
            throw new Exception($class.' can not be resolved to an existing class');
        }

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
                    $args[$param_name] = $this->findParentService($name, $type_class);
               }
            }

            return $this->createInstance($name, $reflection, $args);
        }
    }


    /**
     * Traverse services with parents and find correct service to use
     *
     * @param  string $name
     * @param  string $class
     * @return mixed
     */
    protected function findParentService(string $name, string $class)
    {
        if(isset($this->config[$name]['service'][$class])) {
            return $this->get($this->config[$name]['service'][$class]);
        } elseif(isset($this->config[$name]['parent'])) {
            return $this->findParentService($this->config[$name]['parent'], $class);
        } else {
            return $this->get($class);
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
            if(isset($this->config[$name]['adapter'])) {
                foreach($this->config[$name]['adapter'] as $adapter => $config) {
                    if(!isset($config['name'])) {
                        throw new Exception('adapter requires name configuration');
                    }

                    if(isset($config['enabled']) && $config['enabled'] === '0') {
                        continue;
                    }

                    $adapter_instance = $this->get($config['name']);
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
