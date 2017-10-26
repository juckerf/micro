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
    private const DELIMITER = '_';

    /**
     * Store
     *
     * @var Config
     */
    private $store;

    /**
     * Load config
     *
     * @param  string $config
     * @param  string $env
     * @return void
     */
    public function __construct(string $config = null, string $env = null)
    {
        $this->store = $this->variablesToTree(array_merge($_ENV, $_SERVER));
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
     * Get native config format as config instance
     *
     * @param   array $environmentVariables
     * @return  Config
     */
    public function map($environmentVariables = null): Config
    {
        if ($environmentVariables === null) {
            return $this->store;
        }
        return $this->variablesToTree($environmentVariables);
    }


    /**
     * Transform array of environment variables into a tree
     *
     * @param   array $environmentVars
     * @return  Config
     */
    protected function variablesToTree(array $environmentVars): Config
    {
        // transform keys to lowercase
        $environmentVars = array_change_key_case($environmentVars);

        // initialize root node
        $root = new Config();
        foreach ($environmentVars as $name => $value) {
            // split variable name by delimiter
            $name = explode(self::DELIMITER, $name);

            // go back to root
            $tree = $root;

            // iterate over key parts
            for ($i = 0; $i < count($name); $i++) {
                $key = $name[$i];

                // try to access subtree
                try {
                  // create new subtree if requested subtree already has a value
                  if (!is_a($tree->$key, '\Micro\Config')) {
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
