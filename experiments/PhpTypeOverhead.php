<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

/*
 * PURPOSE:
 * Understand PHP's memory usage for built-in types to make it easier to design memory-efficient
 * data structures to represent the AST.
 *
 * RESULTS:
 * See output at php-types.txt
 *
 * CONCLUSION:
 * Memory management is going to be really hard. We'll need to get *really* creative.
 */

function checkMemory($type, $initializer) {
    $size = 1000000;
    $arr = range(0, $size);
    $startMemory = memory_get_usage(true);
    $array = SplFixedArray::fromArray($arr);

    for ($i = 0; $i < $size; $i++) {
        $array[$i] = $initializer();
    }

    $endMemory = memory_get_usage(true);

    $memoryPerType = ($endMemory - $startMemory) / $size;
    echo "$type\t", $memoryPerType, PHP_EOL;
    unset($arr);
    unset($array);
    gc_mem_caches();
}

checkMemory("empty object", function () {
    return new class {
    };
});

checkMemory("object (1 prop)", function () {
    return new class {
        public $a;
    };
});

checkMemory("object (2 props)", function () {
    return new class {
        public $a;
        public $b;
    };
});

checkMemory("object (3 props)", function () {
    return new class {
        public $a;
        public $b;
        public $c;
    };
});

checkMemory("object (4 props)", function () {
    return new class {
        public $a;
        public $b;
        public $c;
        public $d;
    };
});

checkMemory("object (4 str props)", function () {
    return new class {
        public $a = "12345678";
        public $b = "12345678";
        public $c = "12345678";
        public $d = "12345678";
    };
});

checkMemory("string (empty)", function () {
    return "";
});
checkMemory("string (1)", function () {
    return str_repeat("1", 1);
});
checkMemory("string (2)", function () {
    return str_repeat("1", 2);
});

checkMemory("string (8)", function () {
    return str_repeat("1", 8);
});

checkMemory("string (9)", function () {
    return str_repeat("1", 9);
});

checkMemory("int/long", function () {
    return 1;
});

checkMemory("double/float", function () {
    return 1.5;
});

for ($i = 0; $i < 16; $i++) {
    checkMemory("SplFixedArray($i)", function () use ($i) {
        return new SplFixedArray($i);
    });
}

for ($i = 0; $i < 16; $i++) {
    checkMemory("SplFixedArray::fromArray($i)", function () use ($i) {
        return SplFixedArray::fromArray(range(1, $i));
    });
}

checkMemory("array (empty)", function () {
    return array();
});

for ($i = 0; $i < 16; $i++) {
    checkMemory("array $i", function () use ($i) {
        return range(1, $i);
    });
}

checkMemory("packed ints(1)", function () {
    return pack("I", 1);
});

checkMemory("packed ints(2)", function () {
    return pack("I2", 1, 2);
});

checkMemory("packed ints(3)", function () {
    return pack("I3", 1, 2, 3);
});

checkMemory("packed ints(4)", function () {
    return pack("I4", 1, 2, 3, 4);
});
