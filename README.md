# Micro (Yet another PHP Framework)

## Description
Micro provides minimalistic core features to write a new Application. Instead providing a rich featured fatty library it only provides a couple of classes. It comes with a logger, configuration parser, HTTP routing and some wrapper around databases/ldap.

## Requirements
The library is only >= PHP7.1 compatible.

## Quick start


## API Documentation
### Configuration (\Micro\Config)

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

### Logger (\Micro\Log)

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





### HTTP (\Micro\Http)
 new Router($_SERVER, $this->logger);
