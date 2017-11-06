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

class Environment implements ConfigInterface
{
    /**
     * DELIMITER
     *
     * @var string
     */
    const DEFAULT_DELIMITER = '_';


    /**
     * Store
     *
     * @var Config
     */
    private $store;


    /**
     * Variable delimiter
     *
     * @var string
     */
    protected $delimiter = self::DEFAULT_DELIMITER;


    /**
     * Variable prefix
     *
     * @var string
     */
    protected $prefix;


    /**
     * Load config
     *
     * @param  string $prefix
     * @param  string $delimiter
     * @param  array $variables
     * @return void
     */
    public function __construct(?string $prefix=null, $delimiter=self::DEFAULT_DELIMITER, array $variables=[])
    {
        $this->delimiter = $delimiter;
        $this->prefix = strtolower($prefix);

        if(count($variables) === 0) {
            $this->store = $this->variablesToTree(array_merge($_ENV, $_SERVER));
        } else {
            $this->store = $this->variablesToTree($variables);
        }
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
     * Return map
     *
     * @return  Config
     */
    public function map(): Config
    {
        return $this->store;
    }


    /**
     * Transform array of environment variables into a tree
     *
     * @param   array $variables
     * @return  Config
     */
    protected function variablesToTree(array $variables): Config
    {
        $variables = array_change_key_case($variables);

        $root = new Config();
        foreach ($variables as $name => $value) {
            $name = explode($this->delimiter, $name);

            if($this->prefix !== null) {
                if($name[0] !== $this->prefix) {
                    continue;
                } else {
                    array_shift($name);
                }
            }

            $tree = $root;

            for ($i = 0; $i < count($name); $i++) {
                $key = $name[$i];

                try {
                  // create new subtree if requested subtree already has a value
                  if (!($tree->$key instanceof Config)) {
                      $tree[$key] = new Config([$tree->$key]);
                  }

                  $tree = $tree->$key;
                } catch (Exception $e) {
                    // set value if last keypart or create subtree
                    if($i == (count($name) - 1)) {
                        $tree[$key] = $value;
                    } else {
                        $tree[$key] = new Config();
                        $tree = $tree->$key;
                    }
                }
            }
        }
        return $root;
    }
}
