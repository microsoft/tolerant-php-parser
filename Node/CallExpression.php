<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class CallExpression extends Expression {
    /** @var Expression */
    public $callableExpression;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList[] | null */
    public $argumentExpressionList;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::CallExpression);
    }
}