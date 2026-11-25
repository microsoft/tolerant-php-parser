<?php
// Clone with property modifications (PHP 8.5)
class Foo {
    public int $x = 1;
    public string $name = 'foo';
}

$obj = new Foo();
$cloned = clone($obj, ["x" => 42, "name" => "bar"]);
