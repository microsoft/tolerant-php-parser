<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ListIntrinsicExpression extends Expression {

    /** @var Token */
    public $listKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList */
    public $listElements;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::ListIntrinsicExpression);
    }
}