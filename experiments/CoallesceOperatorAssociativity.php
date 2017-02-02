<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

/*
 * PURPOSE:
 * Understand the associativity properties of the coallesce operator.
 * This experiment compares the time required to evaluate the following groupings:
 *   None  : (a() ?? b() ?? b());
 *   Right : a() ?? (b() ?? (b()); // # comparisons = 1
 *   Left  : (a() ?? b()) ?? b();  // # comparisons = # of ?? operators
 *
 * RESULTS:
 * None  : 0.00024787425994873
 * Right : 0.00025341987609863
 * Left  : 0.042938680648804
 *
 * CONCLUSION:
 * Confirmed that the coallesce operator matches performance properties of right-grouping,
 * and is therefore likely right associative.
 */

function a() {
    return "a";
}

$parenCount = 3000;

$noGroupingStr = str_repeat('(', $parenCount). 'a() ?? ' . str_repeat('b() ?? ', $parenCount) . 'b()' . str_repeat(')', $parenCount) . ";";
$rightGroupingStr = 'a() ?? ' . str_repeat('(b() ?? ', $parenCount) . 'b()' . str_repeat(')', $parenCount) . ";";
$leftGroupingStr = str_repeat('(', $parenCount) . 'a() ?? ' . str_repeat('b()) ?? ', $parenCount) . 'b()' . ";";

$runBenchmark = function ($str) {
    $benchmark = <<< 'before'
unset ($benchmark);
$benchmark = function () {
$average = 0;
for ($j = 0; $j < 50; $j++) {
    $startTime = microtime(true);
    for ($i = 0; $i < 2000; $i++) {
before;

    $benchmark .= $str;

    $benchmark .= <<< 'after'
    }
    $average += microtime(true) - $startTime;
}
return $average / 50;
};

echo $benchmark(), PHP_EOL;
after;

    eval($benchmark);
};

echo "None  : " , $runBenchmark($noGroupingStr);
echo "Right : " , $runBenchmark($rightGroupingStr);
echo "Left  : ", $runBenchmark($leftGroupingStr);
