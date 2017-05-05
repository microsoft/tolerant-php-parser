
function getArray() {
    $arr = [];
    for($i=0; $i < 1000000; $i++) {
        $arr[$i] = $i;
    }
    return $arr;
}

function* getGenerator() {
    for($i=0; $i < 1000000; $i++) {
        yield $i;
    }
}

$start = new Date();

for ($a of getArray()) {
}
$end = new Date();
console.log ($end - $start);


$start2 = new Date();
for ($a of getGenerator()) {
}
$end2 = new Date();
console.log ($end2 - $start2);

