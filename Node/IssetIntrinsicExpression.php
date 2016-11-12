<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class IssetIntrinsicExpression extends Expression {

    /** @var Token */
    public $issetKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList */
    public $expressions;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::IssetIntrinsicExpression);
    }
}