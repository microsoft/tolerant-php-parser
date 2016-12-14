<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class UseVariableName extends Node {
    /** @var Token | null */
    public $byRef;

    /** @var Token */
    public $variableName;

    public function __construct() {
        parent::__construct(NodeKind::UseVariableName);
    }
}