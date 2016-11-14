<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class BracedExpression extends Node {
    /** @var Token */
    public $openBrace;
    /** @var Expression */
    public $expression;

    /** @var Token */
    public $closeBrace;

    public function __construct() {
        parent::__construct(NodeKind::BracedExpression);
    }

}