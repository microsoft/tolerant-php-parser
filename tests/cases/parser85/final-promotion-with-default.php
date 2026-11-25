<?php
// Final with default values
class Example {
    public function __construct(
        final string $name = "default",
        public final int $count = 0
    ) {}
}
