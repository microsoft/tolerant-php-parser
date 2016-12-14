<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class MethodDeclaration extends Node {
    /** @var Token[] */
    public $modifiers;
    
    use FunctionHeader, FunctionReturnType, FunctionBody;

    public function __construct() {
        parent::__construct(NodeKind::MethodNode);
    }

    public function validateRules() {
        $invalid = parent::validateRules();
        foreach ($this->modifiers as $modifier) {
            if ($modifier->kind === TokenKind::VarKeyword) {
                $invalid[] = new SkippedToken($modifier);
            }
        }
        return $invalid;
    }
}