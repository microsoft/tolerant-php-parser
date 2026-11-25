<?php
// Traditional clone syntax
class Foo {
    public int $x = 1;
}

$obj = new Foo();
$cloned = clone $obj;
