<?php

class User
{
    public function __construct(
        public string $username { set => strtolower($value); }
    ) {}
}
