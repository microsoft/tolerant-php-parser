<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class EchoExpression extends Expression {

    /** @var Token */
    public $echoKeyword;

    /** @var Expression[] */
    public $expressions;

    public function __construct() {
        parent::__construct(NodeKind::EchoExpression);
    }
}