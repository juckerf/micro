# Micro (Yet another PHP Framework)

## Description
Micro provides minimalistic core features to write a new Application. Instead providing a rich featured fatty library it only provides a couple of classes. It comes with a logger, configuration parser, HTTP routing and some wrapper around databases/ldap.

## Requirements
The library is only >= PHP7.1 compatible.

## Quick start

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
