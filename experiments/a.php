<?php

namespace A;
//class X {
//    function __construct() {
//        echo "A\\X";
//    }
//}zzzzzzzzz/////////////dkda//z..
const X = "A\\X";

namespace B;
class X {
    function __construct() {
        echo "B\\X";
    }
}

namespace A;
use B\X;

new X;

// use B\X;
new X;

// doesn't work - can only have a using once
// use A\X;
// echo X;



