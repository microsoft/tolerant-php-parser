<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ErrorControlExpression extends UnaryExpression {

    /** @var Token */
    public $operator;

    /** @var UnaryExpression */
    public $operand;

    public function __construct() {
        parent::__construct(NodeKind::ErrorControlExpression);
    }
}