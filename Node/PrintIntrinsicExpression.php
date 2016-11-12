<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class PrintIntrinsicExpression extends Expression {

    /** @var Token */
    public $printKeyword;

    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::PrintIntrinsicExpression);
    }
}