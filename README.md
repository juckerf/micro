# Micro (Yet another PHP library)
...but no shit

[![Build Status](https://travis-ci.org/gyselroth/micro.svg?branch=master)](https://travis-ci.org/gyselroth/micro)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gyselroth/micro/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/gyselroth/micro/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/gyselroth/micro.svg)](https://packagist.org/packages/gyselroth/micro)
[![GitHub release](https://img.shields.io/github/release/gyselroth/micro.svg)](https://github.com/gyselroth/micro/releases)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/gyselroth/micro/master/LICENSE)

## Description
Micro provides minimalistic core features to write a new Application. Instead providing a rich featured fatty library it only provides a couple of classes. It comes with a logger, configuration parser, HTTP routing, Authentication system and some wrapper around databases and ldap.

* \Micro\Auth
* \Micro\Config
* \Micro\Http
* \Micro\Log

## Requirements
The library is only >= PHP7.1 compatible.

## Download
The package is available at packagst: https://packagist.org/packages/gyselroth/micro

To install the package via composer execute:
```
composer require gyselroth/micro
```

## Configuration (\Micro\Config)

#### Read
Simply read an xml configuration and initialize your configuration:
```php
$config = new \Micro\Config(new \Micro\Config\Xml($path));
var_dump($config->myconfigentry);
string(1) "1"
```

And your actual XML configuration would look like:
```xml
<config version="1.0">
  <production>
    <myconfigentry>1</myconfigentry>
  </production>
</config>
```
Every configuration got configuration environement, if you only have one, just stick with <production> as your first node within <config/>. See environements for further information.


#### Merge 
Merge multiple configuration files:
```php
$config1 = new \Micro\Config\Xml($path);
$config2 = new \Micro\Config\Xml($path);
$config1->merge($config2);
$config = new \Micro\Config($config1);
```

#### Environements
You can request a custom configuration environement:
```php
$config = new \Micro\Config(new \Micro\Config\Xml($path, 'development'));
var_dump($config->myconfigentry);
string(1) "2"
```

While your XML configuration would look like:
```xml
<config version="1.0">
  <production>
    <myconfigentry>1</myconfigentry>
  </production>
  <development>
    <myconfigentry>2</myconfigentry>
  </development>
</config>
```

#### Inheritance
The configuration parser supports inheritance. A simple example would be to just inherit one environement from another:
```xml
<config version="1.0">
  <production>
    <a>a</a>
  </production>
  <development inherits="production">
  </development>
</config>
```

```php
$config = new \Micro\Config(new \Micro\Config\Xml($path, 'development'));
var_dump($config->a);
string(1) "a"
```

You can also inherit single elements (recursively) within one environement and overwrite elements which were inherit in the first place:
```xml
<config version="1.0">
  <production>
    <a>a</a>
    <b inherits="a"/>
    <c>
        <child>c</child>
    </c>
    <d inherits="c">
        <child2>d</child2>
    </d>
  </development>
</config>
```

```php
$config = new \Micro\Config(new \Micro\Config\Xml($path));
var_dump($config->a);
string(1) "a"

var_dump($config->b);
string(1) "a"

var_dump($config->c->child);
string(1) "c"

var_dump($config->d->child);
var_dump($config->d->child2);
string(1) "c"
string(1) "d"
```

It possible as well to access any elements (node path is separated with a "."):
```xml
<config version="1.0">
  <production>
    <a>
        <child>
            <subchild>a</subchild>
        </child>
    </a>
    <b inherits="c">
        <child inherits="a.child">
        <child2 inherits="a.child.subchild">
    </b>
  </development>
</config>
```

```php
$config = new \Micro\Config(new \Micro\Config\Xml($path));
var_dump($config->a->child->subchild);
string(1) "a"

var_dump($config->b->child->subchild);
var_dump($config->b->child2);
string(1) "a"
string(1) "a"
```

#### XML Attributes
There is no more a difference between XML attributes and XML nodes. \Micro\Config\Xml parses both equally. So you can decide or switch on the fly whether a name/value should be an attribute or a node.
```xml
<config version="1.0">
  <production>
    <a enabled="1"/>
  </development>
</config>
```
Meaning the above configuration gets parsed as the same Config object as the following:

```xml
<config version="1.0">
  <production>
    <a>
      <enabled>1</enabled>
    </a>
  </development>
</config>
```

## Logger (\Micro\Log)

#### Description
\Micro\Log is a PSR-3 compatible logger with multiple log adapters.

#### Initialize
```php
$logger = new Logger(Iterable $options);
$logger->info(string $message, array $context);
```

#### Configuration
```php
$logger = new Logger([
  'adapter_name' => [
    'class'  => '\Micro\Log\Adapter\File',
    'config' => [
      'file'        => '/path/to/file',
      'date_format' => 'Y-d-m H:i:s', //http://php.net/manual/en/function.date.php
      'format'      => '{date} {level} {message} {context.category}',
      'level'       => 7 //PSR-3 log levels 1-7
    ],
  ],
  'adapter2_name' => []
]);
```
Of course you can initialize the logger with a configuration object as well (any any other iterable objects):
```xml
<log>
  <adapter_name enabled="1" class="\Micro\Log\Adapter\File">
    <config>
      <file>/path/to/file</file>
      <date_format>Y-d-m H:i:s</date_format>
      <format>{date} {level} {message} {context.category}</format>
      <level>7</level>
    </config
  </adapter_name>
</log>
```

```php
$config = new \Micro\Config(new \Micro\Config\Xml($path));
$logger = new Logger($config);
```

#### Format
The message formate is configured in each adapter separately. Available variables are:
* {message} - The message iteself
* {date} - The current timestamp formatted with the configured date_format option
* {level} - The log level, the configured number will be replaced with a string, for exampe 7 => debug
* {context.} - You can acces each context option and include them in the message format. For example you have a context ['category' => 'router'] then you can configure {context.category} to include this context value within your message.

#### Log adapters
* \Micro\Log\Adapter\File
* \Micro\Log\Adapter\Blackhole
* \Micro\Log\Adapter\Stdout
* \Micro\Log\Adapter\Syslog

You can always create your own log adapter using \Micro\Log\Adapter\AdapterInterface.

## HTTP (\Micro\Http)

#### Initialize router
The http router requires an array with http headers, usually this is $_SERVER and a PSR-3 compatible logger.

```php
$router = new \Micro\Http\Router(array $server, \Psr\Log\LoggerInterface $logger)
```

#### Adding routes

```php
$router = (new \Micro\Http\Router($_SERVER, $logger))
  ->clearRoutingTable() 
  ->addRoute(new \Micro\Http\Router\Route('/api/v1/user', 'MyApp\Rest\v1\User'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1/user/{uid:#([0-9a-z]{24})#}', 'MyApp\Rest\v1\User'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1$', 'MyApp\Rest\v1\Rest'))
  ->addRoute(new \Micro\Http\Router\Route('/api/v1', 'MyApp\Rest\v1\Rest'))
  ->addRoute(new \Micro\Http\Router\Route('/api$', 'MyApp\Rest\v1\Rest'));
  ->run(array $controller_params);
```

The router tries to map a request to the first matching route in his routing table. The request gets mappend to a class and method. Optional parameters/query string gets automatically submitted to the final controller class.

Given the routing table above and the following final controller class:

```php
namespace MyApp\Rest\v1;

class User 
{
    /**
     * GET http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/attributes
     * GET http://localhost/api/v1/user/attributes?uid=540f1fc9a641e6eb708b4618
     */
    public function getAttributes(string $uid=null): \Micro\Http\Response
    {
            
    }

    /**
     * GET http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
     * GET http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function get(string $uid=null): \Micro\Http\Response
    {
            
    }

    /**
     * POST http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/password / POST body password=1234
     * POST http://localhost/api/v1/user/password?uid=540f1fc9a641e6eb708b4618 / POST body password=1234
     * POST http://localhost/api/v1/user/password / POST body password=1234, uid=540f1fc9a641e6eb708b4618
     */
    public function postPassword(string $uid, string $password): \Micro\Http\Response
    {
            
    }
    
    /**
     * DELETE http://localhost/api/v1/user/540f1fc9a641e6eb708b4618/mail
     * DELETE http://localhost/api/v1/user/mail?uid=540f1fc9a641e6eb708b4618
     */
    public function deleteMail(string $uid=null): \Micro\Http\Response
    {
            
    }

    /**
     * DELETE http://localhost/api/v1/540f1fc9a641e6eb708b4618/mail
     * DELETE http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function delete(string $uid=null): \Micro\Http\Response
    {
            
    }

    /**
     * HEAD http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
     * HEAD http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
     */
    public function headExists(string $uid=null): \Micro\Http\Response
    {
            
    }
}
```

#### Response
Each endpoint needs to return a Response object to the router.

```php
/**
 * HEAD http://localhost/api/v1/user/540f1fc9a641e6eb708b4618
 * HEAD http://localhost/api/v1/user?uid=540f1fc9a641e6eb708b4618
 */
public function headExists(string $uid=null): \Micro\Http\Response
{
  if(true) {
    return (new \Micro\Http\Response())->setCode(200)->setBody('user does exists');
  } else {
    return (new \Micro\Http\Response())->setCode(404);  
  }
}
```
