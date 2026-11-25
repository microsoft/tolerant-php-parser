<?php
// Readonly before visibility (PHP allows any order)
class Example {
    public function __construct(
        readonly public string $prop1,
        readonly protected int $prop2,
        readonly private bool $prop3
    ) {}
}
