<?php
declare(strict_types = 1);

/**
 * Micro
 *
 * @author    Raffael Sahli <sahli@gyselroth.net>
 * @copyright Copyright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 * @license   MIT https://opensource.org/licenses/MIT
 */

namespace Micro\Container;

interface AdapterAwareInterface
{
    /**
     * Get default adapter
     *
     * @return array
     */
    public function getDefaultAdapter(): array;


    /**
     * Has adapter
     *
     * @param  string $name
     * @return bool
     */
    public function hasAdapter(string $name): bool;


    /**
     * Inject adapter
     *
     * @param  string $name
     * @param  AdapterInterface $adapter
     * @return AdapterInterface
     */
    //public function injectAdapter(string $name, AdapterInterface $adapter): AdapterInterface;


    /**
     * Get adapter
     *
     * @param  string $name
     * @return AdapterInterface
     */
    public function getAdapter(string $name)/*: AdapterInterface*/;


    /**
     * Get adapters
     *
     * @param  array $adapters
     * @return array
     */
    public function getAdapters(array $adapters = []): array;
}
