<?php
class A {
    use \A {
        \a as b;
        \b insteadof C;
    }
}