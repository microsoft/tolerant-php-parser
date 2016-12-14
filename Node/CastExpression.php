<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class CastExpression extends UnaryExpression {

    /** @var Token */
    public $openParen;

    /** @var Token */
    public $castType;

    /** @var Token */
    public $closeParen;

    /** @var Variable */
    public $operand;

    public function __construct() {
        parent::__construct(NodeKind::CastExpression);
    }
}