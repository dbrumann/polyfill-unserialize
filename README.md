Polyfill unserialize [![Build Status](https://travis-ci.org/dbrumann/polyfill-unserialize.svg?branch=master)](https://travis-ci.org/dbrumann/polyfill-unserialize)
===

Backports unserialize options introduced in PHP 7.0 to older PHP versions. This was originally designed as a Proof of Concept for Symfony Issue [symfony/symfony#21090](https://github.com/symfony/symfony/pull/21090) to possibly add this to [Symfony's Polyfills](https://gtihub.com/symfony/polyfill) library.

I don't really use this and therefore do not maintain this actively. Should you have questions or comments feel free to create an Issue.

Requirements
------------

PHP 5.4+

Short array syntax is used in the polyfill. If you need this for older versions you can replace `[]` with `array()`.
