<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @copyright Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 */

namespace Micro\Log\Adapter;

use \Micro\Exception;

interface AdapterInterface
{
    /**
     * Logger
     *
     * @param   string $level
     * @param   string $log
     */
    public function log(string $level, string $log): bool;

    
    /**
     * Create adapter
     *
     * @param Iterable $options
     * @return void
     */
    public function __construct(?Iterable $config=null);
    

    /**
     * Get format
     *
     * @return string
     */
    public function getFormat(): string;


    /**
     * Get date format
     */
    public function getDateFormat(): string;
    
    
    /**
     * Get level
     *
     * @return int
     */
    public function getLevel(): int;
    

    /**
     * Set options
     *
     * @param   Iterable $options
     * @return  AdapterInterface
     */
    public function setOptions(?Iterable $config=null): AdapterInterface;
}
