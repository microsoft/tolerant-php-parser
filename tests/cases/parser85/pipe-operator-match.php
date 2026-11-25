<?php
// Pipe operator in match expression
function splitString(string $s): array {
    return explode(' ', $s);
}

$string = "Hello World";
$newString = match ($format) {
    'snake_case' => $string
        |> splitString(...)
        |> fn($x) => implode('_', $x)
        |> strtolower(...),
    'lowerCamel' => $string
        |> splitString(...)
        |> (fn($x) => array_map(ucfirst(...), $x))
        |> fn($x) => implode('', $x)
        |> lcfirst(...),
};
