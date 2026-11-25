<?php
// Chained pipe operators
$result = "Hello World"
    |> htmlentities(...)
    |> str_split(...)
    |> (fn($x) => array_map(strtoupper(...), $x))
    |> (fn($x) => array_filter($x, fn($v) => $v != 'O'));
