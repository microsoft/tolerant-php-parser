<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class UnsetIntrinsicExpression extends Expression {

    /** @var Token */
    public $unsetKeyword;

    /** @var Token */
    public $openParen;

    /** @var DelimitedList */
    public $expressions;

    /** @var Token */
    public $closeParen;

    public function __construct() {
        parent::__construct(NodeKind::EchoExpression);
    }
}