<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

trait FunctionBody {
    /** @var null | CompoundStatementNode | Token */
    public $compoundStatementOrSemicolon;
}