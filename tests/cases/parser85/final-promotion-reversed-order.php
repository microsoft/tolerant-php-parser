<?php
// Final before visibility (PHP allows any order)
class Example {
    public function __construct(
        final public string $prop1,
        final protected int $prop2,
        final private bool $prop3
    ) {}
}
