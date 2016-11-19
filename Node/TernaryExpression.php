<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class TernaryExpression extends Expression {

    /** @var Expression */
    public $condition;

    /** @var Token */
    public $questionToken;

    /** @var Expression */
    public $ifExpression;

    /** @var Token */
    public $colonToken;

    /** @var Expression */
    public $elseExpression;

    public function __construct() {
        parent::__construct(NodeKind::TernaryExpression);
    }
}