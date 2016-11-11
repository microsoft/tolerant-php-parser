<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class EvalIntrinsicExpression extends Expression {

    /** @var Token */
    public $evalKeyword;

    /** @var Token */
    public $openParen;

    /** @var Expression */
    public $expression;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::EvalIntrinsicExpression);
    }
}