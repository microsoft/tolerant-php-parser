<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/syntax-visualizer/client')
    ->in(__DIR__ . '/syntax-visualizer/server')
    ->in(__DIR__ . '/tests')
    ->exclude('cases')
    ->in(__DIR__ . '/tools')
    ->in(__DIR__ . '/validation');

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true);
