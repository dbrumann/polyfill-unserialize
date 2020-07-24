<?php

namespace Tests\Brumann\Polyfill;

use Brumann\Polyfill\Unserialize;
use PHPUnit\Framework\TestCase;

class UnserializeTest extends TestCase
{
    public function provideInstances()
    {
        return array(
            'Empty class' => array(new Foo(), 'Tests\\Brumann\\Polyfill\\Foo'),
            'Class with uninitialized properties' => array(new Bar(), 'Tests\\Brumann\\Polyfill\\Bar'),
            'Class with object properties' => array(new FooBar(), 'Tests\\Brumann\\Polyfill\\FooBar'),
        );
    }

    /**
     * @dataProvider provideInstances
     */
    public function test_without_options_returns_object($object, $expectedUnserialized)
    {
        $serialized = serialize($object);

        $unserialized = Unserialize::unserialize($serialized);

        $this->assertInstanceOf($expectedUnserialized, $unserialized);
    }

    /**
     * @dataProvider provideInstances
     */
    public function test_with_class_allowed_returns_object($object, $expectedUnserialized)
    {
        $serialized = serialize($object);
        $options = array(
            'allowed_classes' => array($expectedUnserialized),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf($expectedUnserialized, $unserialized);
    }

    /**
     * @dataProvider provideInstances
     */
    public function test_with_false_returns_incomplete_class($object, $originalClass)
    {
        $serialized = serialize($object);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
        $this->assertNotInstanceOf($originalClass, $unserialized);
    }

    public function test_with_prefixed_class_names_returns_incomplete_class()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('\\Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }

    /**
     * @requires PHP < 7.0
     *
     * @expectedException \PHPUnit_Framework_Error_Warning
     * @expectedMessage allowed_classes option should be array or boolean
     */
    public function test_with_invalid_type_raises_error()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => null,
        );

        Unserialize::unserialize($serialized, $options);
    }

    /**
     * @requires PHP < 7.0
     *
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function test_with_invalid_type_returns_incomplete_class()
    {
        $foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => null,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Notice
     * @expectedExceptionMessage tried to execute a method or access a property of an incomplete object.
     */
    public function test_with_parent_not_allowed_serialized_class_is_not_accessible()
    {
        $bar = new \stdClass();
        $bar->foo = new Foo();
        $serialized = serialize($bar);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
        $unserialized->foo;
    }

    public function test_with_only_parent_allowed_property_is_not_accessible()
    {
        $foo = new Foo();
        $foo->bar = new \stdClass();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized);
        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized->bar);
    }

    public function test_parent_containing_same_class_both_are_returned_as_objects()
    {
        $foo = new Foo();
        $foo->foo = new Foo();
        $serialized = serialize($foo);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized);
        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized->foo);
    }

    public function test_private_property_class_returns_objects()
    {
        $foobar = new FooBar();
        $serialized = serialize($foobar);
        $options = array(
            'allowed_classes' => array('Tests\\Brumann\\Polyfill\\FooBar', 'Tests\\Brumann\\Polyfill\\Foo'),
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\FooBar', $unserialized);
        $this->assertInstanceOf('Tests\\Brumann\\Polyfill\\Foo', $unserialized->getFoo());
        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized->bar);
    }

    public function provideInternalTypes()
    {
        return array(
            'string' => array('This is an ordinary string'),
            'int' => array(123),
            'bool' => array(true),
            'array' => array(
                array(
                    'key' => 42,
                    1 => 'foo',
                    'bar' => 'baz',
                    2 => 23,
                    4 => true,
                ),
            ),
        );
    }

    /**
     * @dataProvider provideInternalTypes
     */
    public function test_with_false_returns_internal_types($internalType)
    {
        $serialized = serialize($internalType);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertSame($internalType, $unserialized);
    }

    public function test_double_serialized_unserializes_as_first_serialized()
    {
        $foo = new Foo();
        $first = serialize($foo);
        $second = serialize($first);
        $options = array(
            'allowed_classes' => false,
        );

        $unserialized = Unserialize::unserialize($second, $options);

        $this->assertSame($first, $unserialized);
    }

    public function test_double_unserialize_double_serialized()
    {
        $foo = new Foo();
        $serialized = serialize(serialize($foo));
        $options = array(
            'allowed_classes' => false,
        );

        $first = Unserialize::unserialize($serialized, $options);
        $unserialized = Unserialize::unserialize($first, $options);

        $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
    }

    public function test_nested_serialized_object_in_serialized_object_can_be_deserialized()
    {
        $inner = new \stdClass();
        $outer = new \stdClass();
        $inner->value = serialize('inner');
        $outer->value = serialize(array('item', $inner));
        $serialized = serialize($outer);
        $options = array('allowed_classes' => false);

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertEquals($outer,$unserialized);
    }

    public function test_string_containing_serialized_literals_can_be_deserialized()
    {
        $string = 'A serialized object might look like `...;O:9:"ClassName":0:{};...` - watch out!';
        $serialized = serialize($string);
        $options = array('allowed_classes' => false);

        $unserialized = Unserialize::unserialize($serialized, $options);

        $this->assertEquals($string, $unserialized);
    }
}
