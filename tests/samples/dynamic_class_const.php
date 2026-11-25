<?php

class Example {
    public const FOO = 'foo';
    public const BAR = 'bar';
}

function pickConst(): string {
    return 'FOO';
}

$name = 'BAR';

// Dynamic class constant fetch (PHP 8.3)
var_dump(Example::{pickConst()});
var_dump(Example::{ $name });
