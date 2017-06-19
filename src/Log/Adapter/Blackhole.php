<?php
declare(strict_types=1);

/**
 * Micro
 *
 * @copyright Copryright (c) 2017 gyselroth GmbH (https://gyselroth.com)
 */

namespace Micro\Log\Adapter;

class Blackhole extends AbstractAdapter
{
    /**
     * Log
     *
     * @param   string $level
     * @param   string $message
     * @return  bool
     */
    public function log(string $level, string $message): bool
    {
        return true;
    }
}
