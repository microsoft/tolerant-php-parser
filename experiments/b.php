<?php
const B = 1000900;

function getArray() {
    $arr = [];
    for($i=0; $i < B; $i++) {
        $arr[] = $i;
    }
    return $arr;
}

function getGenerator() {
    for($i=0; $i < B; $i++) {
        yield $i;
    }
}

for ($i = 0; $i < 10000; $i++) {
$start = microtime(true);
    
foreach (getGenerator() as $a) {
}
echo "$i hi ";
echo (microtime(true) - $start) . PHP_EOL;


$start = microtime(true);

foreach (getArray() as $a) {
}
echo (microtime(true) - $start) . PHP_EOL;

}
