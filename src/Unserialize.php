<?php

namespace Brumann\Polyfill;

final class Unserialize
{
    /**
     * @see https://secure.php.net/manual/en/function.unserialize.php
     *
     * @param string $serialized Serialized data
     * @param array $options Associative array containing options
     *
     * @return mixed
     */
    public static function unserialize($serialized, array $options = array())
    {
        if (PHP_VERSION_ID >= 70000) {
            return \unserialize($serialized, $options);
        }

        if (!array_key_exists('allowed_classes', $options)) {
            $options['allowed_classes'] = true;
        }
        $allowedClasses = $options['allowed_classes'];
        if (true === $allowedClasses) {
            return \unserialize($serialized);
        }
        if (false === $allowedClasses) {
            $allowedClasses = [];
        }
        if (!is_array($allowedClasses)) {
            trigger_error(
                'unserialize(): allowed_classes option should be array or boolean',
                E_USER_WARNING
            );
        }
        $sanitizedSerialized = preg_replace_callback(
            '/\bO:\d+:"([^"]*)":(\d+):{/',
            function ($matches) use ($allowedClasses) {
                if (in_array($matches[1], $allowedClasses)) {
                    return $matches[0];
                } else {
                    return sprintf(
                        'O:22:"__PHP_Incomplete_Class":%d:{s:27:"__PHP_Incomplete_Class_Name";%s',
                        $matches[2] + 1,
                        // length of object + 1 for added string
                        \serialize($matches[1])
                    );
                }
            },
            $serialized
        );

        return \unserialize($sanitizedSerialized);
    }
}
