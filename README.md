Polyfill unserialize [![Build Status](https://travis-ci.org/dbrumann/polyfill-unserialize.svg?branch=master)](https://travis-ci.org/dbrumann/polyfill-unserialize)
===

Backports unserialize options introduced in PHP 7.0 to older PHP versions.
This was originally designed as a Proof of Concept for Symfony Issue
[#21090](https://github.com/symfony/symfony/pull/21090).

🚨⚠️ **There is a known bug in all 1.x-versions that will raise an error in certain edge cases.**

Please read the section known issues for more details and possible solutions to this bug.

You can use this package in projects that rely on PHP versions older than
PHP 7.0. In case you are using PHP 7.0+ the original `unserialize()` will be
used instead.

From the [documentation](https://secure.php.net/manual/en/function.unserialize.php):

> **Warning**
>
> Do not pass untrusted user input to unserialize() regardless of the options
> value of allowed_classes. Unserialization can result in code being loaded and
> executed due to object instantiation and autoloading, and a malicious user
> may be able to exploit this. Use a safe, standard data interchange format
> such as JSON (via json_decode() and json_encode()) if you need to pass
> serialized data to the user.

Requirements
------------

 - PHP 5.3+

Installation
------------

You can install this package via composer:

```bash
composer require brumann/polyfill-unserialize "^1.0"
```

Known Issues
------------

**Warning when `allowed_classes` contains invalid input**

There is a mismatch in behavior when `allowed_classes` in `$options` is not
of the correct type (array or boolean). PHP 7.0 will not issue a warning that
an invalid type was provided. This library will trigger a warning, similar to
the one PHP 7.1+ will raise and then continue, assuming `false` to make sure
no classes are deserialized by accident.

**Unserializing serialized data in nested objects**

Version 1.x of `polyfill-unserialize` contains a bug with nested structures
(objects or arrays), which contain a serialized string containing objects, are
serialized. Here is an example for an object that will run into this issue:

```php
$inner = new \stdClass();
$outer = new \stdClass();
$inner->value = serialize('inner');
$outer->value = serialize(['item', $inner]);
```

You can find more details in the related
[issue ticket #10](https://github.com/dbrumann/polyfill-unserialize/issues/10).

For these cases unserialize will not work and in fact will throw an error. This
issue was fixed in version 2.0.

You have multiple options for dealing with this issue, such as:

 * upgrade to `brumann/polyfill-unserialize:^2.0`
 * use the core function `unserialize()` for these cases
 * upgrade to PHP 7.x to be able to use core `unserialize()` with `$options`
   either directly or indirectly by still relying on `brumann/polyfill-unserialize`

Naturally each of these solutions has their own pros/cons.

 * Upgrading to PHP 7 will yield many additional benefits such as better
   performance, but might not be possible due to other dependencies, system or
   business requirements.
 * `brumann/polyfill-unserialize:^2.0` was completely rebuilt to better deal
   with complex structures. This makes the code more complex and might have
   performance implications. We cover the code with as many useful tests as
   possible and we made sure that the new logic passes all these tests, including
   the ones for the newly found issue. That means as far as we know, the library
   work as good as before.
 * Using the core function only for breaking use cases might not be feasible as
   sometimes the input cannot be controlled entirely.

   You might be able to use the core function as a fallback:
   
    ```php
    $unserialized = @Unserialize::unserialize($serialized, $options);
    if (false === $unserialized) {
        $unserialized = \unserialize($serialized);
    }
    ```

Which option you choose is up to you. We recommend going in the above order:
Upgrading to PHP 7, upgrading the library, falling back to the core function.

Tests
-----

You can run the test suite using PHPUnit. It is intentionally not bundled as
dev dependency to make sure this package has the lowest restrictions on the
implementing system as possible.

Please read the [PHPUnit Manual](https://phpunit.de/manual/current/en/installation.html)
for information how to install it on your system.

Please make sure to pick a compatible version. If you use PHP 5.6 you should
use PHPUnit 5.7.27 and for older PHP versions you should use PHPUnit 4.8.36.
Older versions of PHPUnit might not support namespaces, meaning they will not
work with the tests. Newer versions only support PHP 7.0+, where this library
is not needed anymore. 

You can run the test suite as follows:

```bash
phpunit -c phpunit.xml.dist tests/
```

Contributing
------------

This package is considered feature complete. As such I will likely not update
it unless there are security issues.

Should you find any bugs or have questions, feel free to submit an Issue or a
Pull Request on GitHub.

Development setup
-----------------

This library contains a docker setup for development purposes. This allows
running the code on an older PHP version without having to install it locally.

You can use the setup as follows:

1. Go into the project directory

1. Build the docker image

    ```
    docker build -t polyfill-unserialize .
    ```

    This will download a debian/jessie container with PHP 5.6 installed. Then
    it will download an appropriate version of phpunit for this PHP version.
    It will also download composer. It will set the working directory to `/opt/app`.
    The resulting image is tagged as `polyfill-unserialize`, which is the name
    we will refer to, when running the container. 

1. You can then run a container based on the image, which will run your tests

    ```
    docker run -it --rm --name polyfill-unserialize-dev -v "$PWD":/opt/app polyfill-unserialize
    ```

    This will run a docker container based on our previously built image.
    The container will automatically be removed after phpunit finishes.
    We name the image `polyfill-unserialize-dev`. This makes sure only one
    instance is running and that we can easily identify a running container by
    its name, e.g. in order to remove it manually.
    We mount our current directory into the container's working directory.
    This ensures that tests run on our current project's state.

You can repeat the final step as often as you like in order to run the tests.
The output should look something like this:

```bash
dbr:polyfill-unserialize/ (improvement/dev_setup*) $ docker run -it --rm --name polyfill-unserialize-dev -v "$PWD":/opt/app polyfill-unserialize
Loading composer repositories with package information
Installing dependencies (including require-dev) from lock file
Nothing to install or update
Generating autoload files
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

......................                                            22 / 22 (100%)

Time: 167 ms, Memory: 13.25MB

OK (22 tests, 31 assertions)
```

When you are done working on the project you can free up disk space by removing
the initially built image:

```
docker image rm polyfill-unserialize
```
