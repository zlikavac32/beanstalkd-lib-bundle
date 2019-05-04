# Beanstalkd lib bundle

[![Build Status](https://travis-ci.org/zlikavac32/beanstalkd-lib-bundle.svg?branch=master)](https://travis-ci.org/zlikavac32/beanstalkd-lib-bundle)

Bundle for [zlikavac32/beanstalkd-lib](https://github.com/zlikavac32/beanstalkd-lib)

## Table of contents

1. [Installation](#installation)
1. [Configuration](#configuration)
1. [Usage](#usage)
    1. [Tube definition](#tube-definition)
    1. [Runner definition](#runner-definition)
    1. [Server controller](#server-controller)
1. [Examples](#examples)

## Installation

Recommended installation is through Composer.

```
composer require zlikavac32/beanstalkd-lib-bundle
```

Bundle must be enabled in `config/bundles.php`.

```php
return [
    /* ... */

    Zlikavac32\BeanstalkdLibBundle\BeanstalkdLibBundle::class => ['all' => true],

    /* ... */
]
```

Next add default configuration entry for `beanstalkd_lib`. For example, in `config/services/beanstalkd_lib.yaml`. Check [Configuration](#configuration) section for more info.

```yaml
beanstalkd_lib: ~
```

This library uses [zlikavac32/nsb-decorators](https://github.com/zlikavac32/nsb-decorators) which requires custom autoloader in application entry points like `bin/console`.

```php
use Zlikavac32\NSBDecorators\Proxy;

/* ... */

spl_autoload_register(Proxy::class.'::loadFQN');
```

Async signals are also a requirement.

```php
pcntl_async_signals(true);
```

## Configuration

Default configuration looks like:

```yaml
beanstalkd_lib:
    adapters:
        socket: Zlikavac32\BeanstalkdLib\Adapter\PHP\Socket\NativePHPSocket
        yaml_parser: Zlikavac32\BeanstalkdLib\Adapter\Symfony\Yaml\SymfonyYamlParser
    server:
        host: 127.0.0.1
        port: 11300
```

Custom adapters can be implemented for better integration with existing software.

## Usage

This section explains how to use this bundle.

### Tube definition

To use beanstalkd lib client, every managed tube must be configured.

```yaml
tube.domain_tube: # keys value itself is not important to the bundle
    class: Zlikavac32\BeanstalkdLib\Client\TubeConfiguration\StaticTubeConfiguration
    arguments:
        $defaultDelay: 0
        $defaultPriority: 1024
        $defaultTimeToRun: 60
        $defaultTubePauseDelay: 86400
        $serializer: '@DomainSerializer'
    tags:
        - { name: tube_configuration, tube: brute_force_hash }
```

Tag `tube_configuration` collects tube configurations and links them with the client.

Optionally, one could also use `linker` tag to link with the serializer, as can be seen in the [examples/full/container.yaml](examples/full/container.yaml).

### Runner definition

To define tube runner, we can use `job_runner` tag. This tag collects runners and links them with the job dispatcher.

```yaml
Foo\Runner\SomeRunnerClass:
    tags:
        - { name: job_runner, tube: tube_for_this_runner }
```

Additional decorators can be applied either manually or through `decorator` tag, as can be seen in the [examples/full/container.yaml](examples/full/container.yaml).

To run existing runners, use `bin/console worker:run`.

### Server controller

Simple REPL controller for the client is provided as `bin/console worker:controller`. It can be used also to run standalone commands.

Features include:

- list tubes
- pause/unpause tubes
- print stats (with optional refresh)
- flush tube/tubes

## Examples

You can see more examples with code comments in [examples](/examples).
