<?php
// PHP 8.3+ arbitrary static variable initializers
// Prior to PHP 8.3, static variables could only be initialized with constant expressions

function simpleStaticInit() {
    // Traditional constant initializer
    static $counter = 0;
    return ++$counter;
}

function arrayStaticInit() {
    // Array expression initializer (PHP 8.3+)
    static $cache = ['key' => 'value'];
    return $cache;
}

function functionCallInit() {
    // Function call as initializer (PHP 8.3+)
    static $timestamp = time();
    return $timestamp;
}

function objectCreationInit() {
    // Object creation as initializer (PHP 8.3+)
    static $object = new stdClass();
    return $object;
}

function complexExpressionInit() {
    // Complex expression with operators (PHP 8.3+)
    static $result = 10 + 20 * 3;
    return $result;
}

function multipleStaticVars() {
    // Multiple static variables with different initializers
    static $a = 1;
    static $b = [1, 2, 3];
    static $c = strlen('test');
    static $d = new ArrayObject();
    return [$a, $b, $c, $d];
}

function nestedFunctionCallInit() {
    // Nested function calls (PHP 8.3+)
    static $value = strtoupper(trim('  hello  '));
    return $value;
}

function staticArrayAccess() {
    // Array access in initializer (PHP 8.3+)
    static $data = ['a', 'b', 'c'];
    static $first = $data[0] ?? 'default';
    return $first;
}

function staticWithConstants() {
    // Using class constants (PHP 8.3+)
    static $max = PHP_INT_MAX;
    static $dir = __DIR__;
    return [$max, $dir];
}

class StaticInMethods {
    public function methodWithStatic() {
        // Static in method with function call (PHP 8.3+)
        static $initialized = microtime(true);
        return $initialized;
    }

    public static function staticMethodWithStatic() {
        // Static variable in static method
        static $cache = ['cached' => true];
        return $cache;
    }
}

function conditionalExpressionInit() {
    // Ternary operator in initializer (PHP 8.3+)
    static $value = true ? 'yes' : 'no';
    return $value;
}

function nullCoalesceInit() {
    // Null coalesce in initializer (PHP 8.3+)
    static $config = null ?? 'default';
    return $config;
}
