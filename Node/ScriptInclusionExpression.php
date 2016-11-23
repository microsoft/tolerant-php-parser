<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class ScriptInclusionExpression extends Expression  {
    /** @var Token */
    public $requireOrIncludeKeyword;
    /** @var Expression */
    public $expression;

    public function __construct() {
        parent::__construct(NodeKind::ScriptInclusionExpressoin);
    }
}