<?php
// Final with readonly
class Example {
    public function __construct(
        public readonly final string $prop1,
        final readonly string $prop2
    ) {}
}
