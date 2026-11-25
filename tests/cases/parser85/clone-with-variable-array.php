<?php
// Clone with variable array
class Foo {
    public int $x = 1;
    public string $name = 'foo';
}

$obj = new Foo();
$modifications = ["x" => 99, "name" => "modified"];
$cloned = clone($obj, $modifications);
