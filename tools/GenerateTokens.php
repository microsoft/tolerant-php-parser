<?php

use Microsoft\PhpParser\Token\Compiler;
use Microsoft\PhpParser\Token\TokenCompiler;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/tokens.php";


$tokenCompiler = new TokenCompiler(new Compiler());

$file = __DIR__ . "/../src/TokenKind.php";
$data = utf8_encode($tokenCompiler->compile($tokens));

file_put_contents($file, $data);
echo $data;

