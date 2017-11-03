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

use \Psr\Log\LoggerInterface as Logger;

class None extends AbstractAdapter
{
    /**
     * Authenticate
     *
     * @return bool
     */
    public function authenticate(): bool
    {
        return true;
    }


    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return '';
    }


    /**
     * Get attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return [];
    }
}
