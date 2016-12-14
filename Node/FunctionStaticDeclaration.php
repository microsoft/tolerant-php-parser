<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class FunctionStaticDeclaration extends StatementNode {

    /** @var Token */
    public $staticKeyword;

    /** @var DelimitedList[] */
    public $staticVariableNameList;

    /** @var Token */
    public $semicolon;

    public function __construct() {
        parent::__construct(NodeKind::FunctionStaticDeclaration);
    }
}