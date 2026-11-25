<?php

class Counter {
    private int $value = 0;

    public int $count {
        get => $this->value;
        set (int $newValue) {
            $this->value = max(0, $newValue);
        }
    }
}

$counter = new Counter();
$counter->count = 5;
var_dump($counter->count);
