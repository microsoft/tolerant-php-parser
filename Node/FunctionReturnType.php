<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

trait FunctionReturnType {
    /** @var Token */
    public $colonToken;
    /** @var null | Name */
    public $returnType;
}