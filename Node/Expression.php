<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;

class Expression extends Node {
    public $children = array();
    public function __construct() {
        parent::__construct(NodeKind::Expression);
    }

}