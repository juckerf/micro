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
     * @return void
     */
    public function __construct(? Iterable $config = null);
    

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
     * @return  AdapterInterface
     */
    public function setOptions(? Iterable $config = null) : AdapterInterface;
}
