<?php
// Final promotion mixed with regular and promoted parameters
class Example {
    public function __construct(
        string $regularParam,
        public string $promoted,
        final string $finalPromoted,
        public final int $publicFinal,
        readonly string $readonlyPromoted
    ) {}
}
