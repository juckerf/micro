<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Fabian Jucker <jucker@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Config;

use \Micro\Config;
use Micro\Config\Environment\EnvironmentNode;

class Environment implements ConfigInterface
{
    /**
     * DELIMITER
     *
     * @var string
     */
    private const DELIMITER = '_';

    /**
     * Store
     *
     * @var EnvironmentNode
     */
    private $store;

    /**
     * Environment
     *
     * @var string
     */
    private $environment;

    /**
     * Load config
     *
     * @param  string $config
     * @param  string $env
     * @return void
     */
    public function __construct(string $config = null, string $env = 'production')
    {
        $this->environment = $env;
        $environmentVars = array_merge($_ENV, $_SERVER);
        $this->store = $this->parseEnvironmentVars($environmentVars);
    }


    /**
     * Get raw format
     *
     * @return mixed
     */
    public function getRaw(): array
    {
        return $this->getRawRecursive([$this->store]);
    }

    /**
     * Get from config
     *
     * @param   EnvironmentNode[] $nodes
     * @return  array
     */
    protected function getRawRecursive(array $nodes): array
    {
        $raw = [];
        foreach ($nodes as $entry) {
            if ($entry->value) {
                $raw[$this->getEnvironmentPrefix() . $entry->getFullKey()] = $entry->value;
            }
            $raw = array_merge($raw, $this->getRawRecursive($entry->children));
        }
        return $raw;
    }

    /**
     * Get from config
     *
     * @param   string $name
     * @return  mixed
     */
    public function __get(string $name)
    {
        return $this->store->{$name};
    }


    /**
     * Add config tree and merge it
     *
     * @param   mixed $config
     * @return  ConfigInterface
     */
    public function merge($config): ConfigInterface
    {
        if (!$config instanceof Environment) {
            throw new Exception('not able to merge objects of other types than ' . static::class);
        }
        $raw = $this->getRaw();

        // ensure right env is set on variables
        foreach ($config->getRaw() as $key => $value) {
            $key = $this->getEnvironmentPrefix() . $config->removeEnvironmentPrefix($key);
            $raw[$key] = $value;
        }
        $this->store = $this->parseEnvironmentVars($raw);
        return $this;
    }


    /**
     * Get native config format as config instance
     *
     * @return  Config
     */
    public function map($environmentVariables = null): Config
    {
        if ($environmentVariables === null) {
            $envConfig = $this->store;
        } else {
            $envConfig = $this->parseEnvironmentVars($environmentVariables);
        }

        $config = new Config();
        foreach ($envConfig->children as $entry) {
            $config[$entry->key] = $entry->toConfig();
        }
        return $config;
    }

    /**
     * Filter environment variables for current environment and parse them into a tree
     *
     * @param   array $environmentVars
     * @return  EnvironmentNode
     */
    protected function parseEnvironmentVars(array $environmentVars)
    {
        // only parse environment variables with valid names
        $environmentVars = array_filter($environmentVars, function ($value, $key) {
            return $this->isValidEnvironmentVariable($key);
        }, ARRAY_FILTER_USE_BOTH);
        // transform to tree
        return $this->variablesToTree($environmentVars);
    }

    /**
     * Transform array of environment variables into a tree
     *
     * @param   array $environmentVars
     * @return  EnvironmentNode
     */
    protected function variablesToTree(array $environmentVars)
    {
        $root = new EnvironmentNode(EnvironmentNode::ROOT_KEY);
        foreach ($environmentVars as $name => $value) {
            // remove environment prefix from variable name
            $name = $this->removeEnvironmentPrefix(strtolower($name));
            // split variable name by delimiter
            $name = explode(self::DELIMITER, $name);

            $tree = $root;
            foreach ($name as $key) {
                if (!$tree->$key) {
                    $tree->addChild(new EnvironmentNode($key));
                }
                $tree = $tree->$key;
            }
            $tree->setValue($value);
        }
        return $root;
    }

    /**
     * Get prefix for current environment
     *
     * @return  string
     */
    protected function getEnvironmentPrefix(): string
    {
        return $this->environment . self::DELIMITER;
    }

    /**
     * Remove current environment prefix from given environment variable name
     *
     * @param   string $name
     * @return  string
     */
    public function removeEnvironmentPrefix(string $name): string
    {
        $prefix = $this->getEnvironmentPrefix();
        if (substr($name, 0, strlen($prefix)) == $prefix) {
            $name = substr($name, strlen($prefix));
        }
        return $name;
    }

    /**
     * Check if a given environment variable name begins with the current environment prefix
     *
     * @param   string $name
     * @return  bool
     */
    protected function hasEnvironmentPrefix(string $name): bool
    {
        $prefix = $this->getEnvironmentPrefix();
        return (0 === strpos($name, $prefix));
    }

    /**
     * Check if a given environment variable name is valid
     *
     * @param   string $name
     * @return  bool
     */
    protected function isValidEnvironmentVariable(string $name): bool
    {
        $name = strtolower($name);
        // variables are valid if their name has the right prefix and doesn't end with the delimiter
        return substr($name, -1) !== self::DELIMITER && $this->hasEnvironmentPrefix($name);
    }
}
