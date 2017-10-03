<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Fabian Jucker <jucker@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Config\Environment;

use \Micro\Config;

class EnvironmentNode
{
    /**
     * ROOT_KEY
     *
     * @var string
     */
    public const ROOT_KEY = '';

    /**
     * Key
     *
     * @var string
     */
    private $key;

    /**
     * Value
     *
     * @var mixed
     */
    private $value;

    /**
     * Children
     *
     * @var EnvironmentNode[]
     */
    private $children;

    /**
     * Parent
     *
     * @var EnvironmentNode
     */
    private $parent;

    /**
     * Create tree node
     *
     * @param  string $key
     * @param  mixed $value
     * @param  EnvironmentNode[] $children
     * @return void
     */
    public function __construct(string $key, $value = null, $children = [])
    {
        self::ensureValueOrChildren($value, $children);

        $this->key = $key;
        $this->value = $value;
        $this->children = [];
        foreach ($children as $child) {
            if ($child instanceof EnvironmentNode) {
                $this->addChild($child);
            }
        }
    }

    /**
     * Add child node to node
     *
     * @param  EnvironmentNode $child
     * @return void
     */
    public function addChild(EnvironmentNode $child)
    {
        self::ensureValueOrChildren($this->value, [$child]);
        $child->setParent($this);
        $this->children[] = $child;
    }

    /**
     * Get entry
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($name)
    {
        // return "native" properties directly
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // otherwise search for children named $name
        foreach ($this->children as $child) {
            if ($child->key === $name) {
                if ($child->value) {
                    return $child->value;
                }
                return $child;
            }
        }
    }

    /**
     * Set value of node
     *
     * @param  mixed $value
     * @return void
     */
    public function setValue($value)
    {
        self::ensureValueOrChildren($value, $this->children);
        if (count($this->children) > 0) {
            throw new Exception('either $value or $children can be set but not both');
        }
        $this->value = $value;
    }

    /**
     * Set parent of node
     *
     * @param  EnvironmentNode $parent
     * @return void
     */
    public function setParent(EnvironmentNode $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Transform node recursively to a Config object
     *
     * @return mixed
     */
    public function toConfig()
    {
        if ($this->value) {
            return $this->value;
        }
        $config = new Config();
        foreach ($this->children as $child) {
            $config[$child->key] = $child->toConfig();
        }
        return $config;
    }

    /**
     * Gets the fully qualified key of the node
     *
     * @return string
     */
    public function getFullKey(): string
    {
        $keyparts = [$this->key];
        $parent = $this->parent;
        while ($parent && $parent->key !== self::ROOT_KEY) {
            $keyparts[] = $parent->key;
            $parent = $parent->parent;
        }

        return implode('_', array_reverse($keyparts));
    }

    /**
     * Ensures that a node has either a value or children, but not both
     *
     * @throws Exception
     * @return void
     */
    protected static function ensureValueOrChildren($value, array $children)
    {
        if ($value && count($children) > 0) {
            throw new Exception('A node can either have a value or children, but not both');
        }
    }
}
