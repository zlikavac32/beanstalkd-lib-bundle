# Beanstalkd lib bundle

## Unrelesed

* **[CHANGED]** Minimum supported `zlikavac32/php-enum` version is 3.0.0
* **[CHANGED]** Minimum supported `zlikavac32/beanstalkd-lib` version is 0.5.0

## 0.2.0 (2020-04-08)

* **[CHANGED]** Typed properties are now used (PHP 7.4 is a minimum version to be used)

## 0.1.3 (2019-08-23)

* **[ADDED]** `flush` server controller command now be provided with job states to flush

## 0.1.2 (2019-05-12)

* **[ADDED]** `delete` server controller command
* **[ADDED]** `wait` server controller command

## 0.1.1 (2019-05-05)

* **[ADDED]** `symfony/var-dump:^4.0` as a new requirement
* **[ADDED]** `ext-posix` as a new requirement
* **[ADDED]** `peek` server controller command
* **[CHANGED]** Server controller refreshes autocomplete list on every iteration
* **[CHANGED]** Minimal version for `symfony/dependency-injection` is `^4.1`
* **[ADDED]** `kick` server controller command
* **[FIXED]** `Zlikavac32\BeanstalkdLibBundle\Command\Runnable\BeanstalkdServerControllerRunnable` crash on empty input line

## 0.1.0 (2019-05-05)

* **[NEW]** First tagged version
