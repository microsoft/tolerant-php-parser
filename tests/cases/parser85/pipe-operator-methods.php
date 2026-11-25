<?php
// Pipe operator with methods and static methods
class Example {
    public function double(int $x): int {
        return $x * 2;
    }
}

class Math {
    public static function square(int $x): int {
        return $x * $x;
    }
}

$obj = new Example();
$result1 = 5 |> $obj->double(...);
$result2 = 5 |> Math::square(...);
$result3 = 10
    |> $obj->double(...)
    |> Math::square(...);
