<?php
class A {
    public $x = & $y; // compile time error, not a syntax error
}
