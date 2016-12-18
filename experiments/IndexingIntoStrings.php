<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

/*
 * PURPOSE:
 * During the course of lexing a file, we will be indexing into strings quite liberally.
 * This experiment compares the effects of indexing into a string and indexing into an associative array.
 *
 * RESULTS:
 * Memory by indexing into string: 31457280
 * Memory by indexing into character array: 0
 *
 * CONCLUSION:
 * We create new zvals every time we index into a string, whereas we are merely incrementing the refcount
 * every time we index into the unpacked character array. Therefore, iterating through a character array is
 * preferable to decrease memory footprint.
 */

$count = 1000000;
$str = str_repeat("a", $count);
$unpackedStr = unpack('C*', $str);

$char = new SplFixedArray($count+1);

$memory = memory_get_peak_usage(true);
for ($i = 1; $i<$count; $i++) {
    $char[$i] = $str[$i];
}
echo "Memory by indexing into string: ", (memory_get_peak_usage(true) - $memory), PHP_EOL;

$memory = memory_get_peak_usage(true);
for ($i = 1; $i<$count+1; $i++) {
    $char[$i] = $unpackedStr[$i];
}
echo "Memory by indexing into character array: ", (memory_get_peak_usage(true) - $memory), PHP_EOL;
