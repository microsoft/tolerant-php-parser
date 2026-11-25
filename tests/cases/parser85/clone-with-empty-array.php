<?php
// Clone with empty modifications array
class Foo {
    public int $x = 1;
}

$obj = new Foo();
$cloned = clone($obj, []);
