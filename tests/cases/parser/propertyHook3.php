<?php

class User
{
    public function __construct(public string $first, public string $last) {}

    public string $fullName {
        get {
            return "$this->first $this->last";
        }
        set(string $value) {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }
}
