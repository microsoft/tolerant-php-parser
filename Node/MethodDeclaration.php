<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class MethodDeclaration extends FunctionDefinition {
    /** @var Token[] */
    public $modifiers;

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