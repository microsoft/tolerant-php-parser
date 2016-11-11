<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ArrayIntrinsicExpression extends Expression {

    /** @var Token */
    public $arrayKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList */
    public $arrayElements;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::ArrayIntrinsicExpression);
    }
}