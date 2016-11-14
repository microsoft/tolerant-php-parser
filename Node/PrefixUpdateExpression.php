<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class PrefixUpdateExpression extends UnaryExpression {

    /** @var Token */
    public $incrementOrDecrementOperator;

    /** @var Variable */
    public $operand;

    public function __construct() {
        parent::__construct(NodeKind::PrefixUpdateExpression);
    }
}