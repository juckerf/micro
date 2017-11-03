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

use Micro\Log;
use Micro\Log\Adapter\AdapterInterface;

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
    public function setOptions(? Iterable $config = null): AdapterInterface
    {
        if ($config === null) {
            return $this;
        }

        foreach ($config as $option => $value) {
            switch ($option) {
                case 'ident':
                    $this->ident = (string)$value;
                    unset($config[$option]);
                break;
                case 'option':
                    $this->option = (int)(string)$value;
                    unset($config[$option]);
                break;
                case 'facility':
                    $this->facility = (string)$value;
                    unset($config[$option]);
                break;
            }
        }

        parent::setOptions($config);

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
        return syslog(Log::PRIORITIES[$level], $message);
    }
}
