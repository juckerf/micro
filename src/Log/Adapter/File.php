<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @copyright Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
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
     * @param   Iterable $options
     * @return  AdapterInterface
     */
    public function setOptions(?Iterable $config=null): AdapterInterface
    {
        parent::setOptions($config);

        if ($config === null) {
            return $this;
        }

        foreach ($config as $attr => $val) {
            switch ($attr) {
                case 'file':
                    $this->file = str_replace('APPLICATION_PATH', APPLICATION_PATH, (string)$val);
                    $this->resource = fopen($this->file, 'a');
                break;
            }
        }

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
        $result = fwrite($this->resource, $message."\n");
        return (bool)$result;
    }
}
