<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ExitIntrinsicExpression extends Expression {

    /** @var Token */
    public $exitOrDieKeyword;

    /** @var Token | null */
    public $openParen;

    /** @var Expression | null */
    public $expression;

    /** @var Token | null */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::ExitIntrinsicExpression);
    }
}