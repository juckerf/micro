<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Auth\Adapter;

interface AdapterInterface
{
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
