<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

require_once(__DIR__ . "/../src/bootstrap.php");

$totalSize = 0;
$directoryIterator = new RecursiveDirectoryIterator(__DIR__ . "/frameworks/WordPress");
$parser = new \Microsoft\PhpParser\Parser();
$sourceFiles = array();

foreach (new RecursiveIteratorIterator($directoryIterator) as $file) {
    if (strpos($file, ".php")) {
        $totalSize += $file->getSize();
        $sourceFiles[] = $parser->parseSourceFile(file_get_contents($file->getPathname()));
    }
}

$asts = [];

$startMemory = memory_get_peak_usage(true);
$startTime = microtime(true);

function iterate($n) {
    $i2 = 0;
    foreach ($n::CHILD_NAMES as $name) {
        $node = $n->$name;

        if ($node === null) {
            continue;
        }

        if (\is_array($node)) {
            foreach ($node as $nodeArrItem) {
                if ($nodeArrItem instanceof \Microsoft\PhpParser\Node) {   
                    $i2++;
                    $i2 += iterate($nodeArrItem);
                }
            }
        } else if ($node instanceof \Microsoft\PhpParser\Node) {
            $i2++;
            $i2 += iterate($node);
        } else {
            $i2++;
        }
    }

    return $i2;
}

$i = 0;
foreach ($sourceFiles as $idx=>$sourceFile) {
    $i += iterate($sourceFile);
    $asts[] = $sourceFile;

    if ($idx % 10 === 0) {
        echo $idx;
    }
    if ($idx > 100) {
        break;
    }
}

echo PHP_EOL . "Total nodes: $i" . PHP_EOL;

if (!isset($idx)) {
    exit("Validation directory does not exist. First run `git submodule update --init --recursive from project root.`");
}

$asts = SplFixedArray::fromArray($asts);

$endTime = microtime(true);
$endMemory = memory_get_peak_usage(true);

// TODO - multiple runs, calculate statistical significance
$memoryUsage = $endMemory - $startMemory;
$timeUsage = $endTime - $startTime;
$totalSize /= 1024*1024;
$memoryUsage /= 1024*1024;

echo "MACHINE INFO\n";
echo "============\n";
echo "PHP int size: " . PHP_INT_SIZE . PHP_EOL;
echo "PHP version: " . phpversion() . PHP_EOL;
echo "OS: " . php_uname() . PHP_EOL . PHP_EOL;

echo "PERF STATS\n";
echo "==========\n";
echo "Input Source Files (#): $idx\n";
echo "Input Source Size (MB): $totalSize\n";
echo PHP_EOL;
echo "Time Usage (seconds): $timeUsage\n";
echo "Memory Usage (MB): $memoryUsage\n";
