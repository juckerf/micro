<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Log\Adapter;

class Syslog extends AbstractAdapter
{
    /**
     * Syslog ident
     *
     * @var string
     */
    protected $ident;


    /**
     * Option
     *
     * @var int
     */
    protected $option = LOG_PID;


    /**
     * Facility
     *
     * @var string
     */
    protected $facility;


    /**
     * Set options
     *
     * @return  AdapterInterface
     */
    public function setOptions(? Iterable $config = null)
    {
        parent::setOptions($options);

        if ($options === null) {
            return $this;
        }

        foreach ($options as $attr => $val) {
            switch ($attr) {
                case 'ident':
                    $this->ident = (string)$val;
                break;
                case 'option':
                    $this->option = (int)(string)$val;
                break;
                case 'facility':
                    $this->facility = (string)$val;
                break;
            }
        }

        openlog($this->ident, $this->option, $this->facility);

        return $this;
    }


    /**
     * Log
     *
     * @param   string $level
     * @param   string $message
     * @return  bool
     */
    public function log(string $level, string $message): bool
    {
        return syslog($level, $message);
    }
}
