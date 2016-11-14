<?php
namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class SubscriptExpression extends Expression {

    /** @var Expression */
    public $postfixExpression;

    /** @var Token */
    public $openBracketOrBrace;

    public $accessExpression;

    /** @var Token */
    public $closeBracketOrBrace;


    public function __construct() {
        parent::__construct(NodeKind::SubscriptExpression);
    }
}