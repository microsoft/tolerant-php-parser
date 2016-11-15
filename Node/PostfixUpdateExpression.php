<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class PostfixUpdateExpression extends Expression {
    /** @var Variable */
    public $operand;

    /** @var Token */
    public $incrementOrDecrementOperator;

    public function __construct() {
        parent::__construct(NodeKind::PostfixUpdateExpression);
    }
}