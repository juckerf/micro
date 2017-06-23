<?php
namespace Micro\Testsuite\Http;

use \PHPUnit\Framework\TestCase;
use \Micro\Http\Router;
use \Micro\Testsuite\MockLogger;

class RouterTest extends TestCase
{
    public function testInit()
    {
        $server = [
            'PATH_INFO' => 'index.php/api/my/path'
        ];

        $this->router = new Router($server, new MockLogger());
        $this->assertEqual($router->getPath(), 'index.php/api/my/path');
    }
}
