<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter;

use \Psr\Log\LoggerInterface as Logger;

interface AdapterInterface
{
    /**
     * Authenticate
     *
     * @param   Iterable $config
     * @param   Logger $logger
     * @return  void
     */
    public function __construct(?Iterable $config, Logger $logger);


    /**
     * Get attribute sync cache
     *
     * @return int
     */
    public function getAttributeSyncCache(): int;


    /**
     * Authenticate
     *
     * @return  bool
     */
    public function authenticate(): bool;


    /**
     * Get unqiue identity name
     *
     * @return string
     */
    public function getIdentifier(): string;
 

    /**
     * Get attribute map
     *
     * @return Iterable
     */
    public function getAttributeMap(): Iterable;
   
    
    /**
     * Get identity attributes
     *
     * @return array
     */
    public function getAttributes(): array;
}
