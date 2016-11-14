<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ArgumentExpression extends Expression {
    /** @var Token | null */
    public $byRefToken; // TODO removed in newer versions of PHP. Also only accept variable, not expression if byRef

    /** @var Token | null */
    public $dotDotDotToken;

    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::ArgumentExpression);
    }
}