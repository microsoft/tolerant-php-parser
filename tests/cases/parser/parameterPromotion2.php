<?php
class X {
    // It's a syntax error to have more than one visibility modifier
    public function __construct(
        public protected $var
    ) {}
}
