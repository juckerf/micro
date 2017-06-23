<?php
namespace Micro\Testsuite;

use \Psr\Log\AbstractLogger;

class MockLogger extends AbstractLogger
{
    public function log($level, $message, array $context=[])
    {
        
    }
}
