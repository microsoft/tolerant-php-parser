<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;

class Expression extends Node {
    public function __construct() {
        parent::__construct(NodeKind::Expression);
    }

}