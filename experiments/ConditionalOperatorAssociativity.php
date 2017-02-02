<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

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
