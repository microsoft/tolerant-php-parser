<?php

class A {
    public static $childNames;

    public $a;

    public function __construct() {
        if (!isset($this::$childNames)) {
            $class = \get_class($this);
            // var_dump($class);
            $reflectionClass = new \ReflectionClass($class);
            $names = [];
            foreach ($reflectionClass->getProperties() as $property) {
                if (($propertyName = $property->name) === "parent") {
                    continue;
                }
                $names[] = $propertyName;
            }
            $this::$childNames = $names;
        }
    }
}

class B extends A {

}

class C extends A {
    public $c;
    public $d;
    
}

$b = new B;
var_dump($b::$childNames);

$c = new C;
var_dump($c::$childNames);