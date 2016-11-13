<?php

/*
 * PURPOSE:
 * Understand the associativity properties of the conditional operator.
 * This experiment compares the result of a nested ternary to the expected
 * left and right associative results.
 *
 * RESULTS:
 * input  : 1
 * output : three
 * => Left-associative :(
 *
 * CONCLUSION:
 * Conditional operators are left associative :(
 */

$i = 1;

$label =
    $i === 1 ? "one" :
    $i === 2 ? "two" :
    $i === 3 ? "three" :
    "error";

echo "input  : ", $i, PHP_EOL;
echo "output : ", $label, PHP_EOL;
echo "=> ";
if ($label === "one") {
    echo "Right-associative :)";
} else {
    echo "Left-associative :(";
}

$a = 1=== 2? false : $d = 3;
echo $d;