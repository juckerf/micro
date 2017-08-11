<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Log\Adapter;

class File extends AbstractAdapter
{
    /**
     * Filename
     *
     * @var string
     */
    protected $file = '/tmp/out.log';

    
    /**
     * Filename
     *
     * @var resource
     */
    protected $resource;

    
    /**
     * Set options
     *
     * @return  AdapterInterface
     */
    public function setOptions(? Iterable $config = null) : AdapterInterface
    {
        parent::setOptions($config);

        if ($config === null) {
            $this->resource = fopen($this->file, 'a');
            return $this;
        }

        foreach ($config as $attr => $val) {
            switch ($attr) {
                case 'file':
                    $this->file = str_replace('APPLICATION_PATH', APPLICATION_PATH, (string)$val);
                break;
            }
        }

        $this->resource = fopen($this->file, 'a');
        return $this;
    }

    
    /**
     * Log
     *
     * @param   int $priority
     * @param   string $message
     * @return  bool
     */
    public function log(string $priority, string $message): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $result = fwrite($this->resource, $message."\n");
        return (bool)$result;
    }
}
