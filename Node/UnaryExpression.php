<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class UnaryExpression extends Expression  {
    /** @var UnaryExpression | Variable */
    public $operand;
}