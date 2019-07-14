<?php

namespace Tests\Brumann\Polyfill;

final class FooBar
{
    private $foo;
    public $bar;

    public function __construct()
    {
        $this->foo = new Foo();
        $this->bar = new Bar();
    }

    public function getFoo()
    {
        return $this->foo;
    }
}
