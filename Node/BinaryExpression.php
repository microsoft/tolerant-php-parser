<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\ParseContext;
use PhpParser\Token;

class BinaryExpression extends Expression {

    /** @var Expression */
    public $leftOperand;

    /** @var Token */
    public $operator;

    /** @var Expression */
    public $rightOperand;

    public function __construct($kind = NodeKind::BinaryExpression) {
        parent::__construct($kind);
    }
}