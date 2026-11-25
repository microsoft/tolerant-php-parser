<?php
// Test public(set) asymmetric visibility (PHP 8.4+)

class Example {
    // public read, public set
    public public(set) string $name;

    // public read, public set with default value
    public public(set) int $count = 0;

    // public(set) in constructor promotion
    public function __construct(
        public public(set) string $id,
        public public(set) int $value = 42
    ) {}

    // Multiple properties with public(set)
    public public(set) array $items = [];
    public public(set) ?object $data = null;
}

// Test all combinations with public(set)
class AllCombinations {
    public function __construct(
        public public(set) int $a,
        public protected(set) int $b,
        public private(set) int $c,
        protected public(set) int $d,
        protected protected(set) int $e,
        protected private(set) int $f
    ) {}
}
