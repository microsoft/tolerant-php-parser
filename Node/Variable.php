<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class Variable extends Node {
    /** @var Token */
    public $dollar;

    /** @var Token | Variable | BracedExpression */
    public $name;

    public function __construct() {
        parent::__construct(NodeKind::Variable);
    }
}