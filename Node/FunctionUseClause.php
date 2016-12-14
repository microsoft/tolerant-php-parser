<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

trait FunctionUseClause {
    /** @var AnonymousFunctionUseClause | null */
    public $anonymousFunctionUseClause;
}