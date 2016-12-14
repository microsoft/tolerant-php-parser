<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class CloneExpression extends Expression {

    /** @var Token */
    public $cloneKeyword;

    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::CloneExpression);
    }
}