<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;

class Expression extends Node {
    public function __construct(int $kind) {
        parent::__construct($kind);
    }

}