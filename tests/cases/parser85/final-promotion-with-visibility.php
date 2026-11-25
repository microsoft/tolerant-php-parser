<?php
// Final with visibility modifiers
class Example {
    public function __construct(
        public final string $publicFinal,
        protected final int $protectedFinal,
        private final bool $privateFinal
    ) {}
}
