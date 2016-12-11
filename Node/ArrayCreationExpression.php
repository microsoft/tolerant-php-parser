<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ArrayCreationExpression extends Expression {

    /** @var Token */
    public $arrayKeyword;

    /** @var Token */
    public $openParenOrBracket;

    /** @var DelimitedList */
    public $arrayElements;

    /** @var Token */
    public $closeParenOrBracket;

    public function __construct() {
        parent::__construct(NodeKind::ArrayCreationExpression);
    }
}