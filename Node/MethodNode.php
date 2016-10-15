<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class MethodNode extends Node {
    /** @var Token[] */
    public $modifiers;
    /** @var Token */
    public $functionKeyword;
    /** @var Token */
    public $byRefToken;
    /** @var null | Name */
    public $name;
    /** @var Token */
    public $openParen;
    /** @var DelimitedList[] */
    public $parameters;
    /** @var Token */
    public $closeParen;
    /** @var null | Name */
    public $returnTypeOpt;
    /** @var null | MethodBlockNode */
    public $compoundStatement;

    public function __construct() {
        parent::__construct(NodeKind::MethodNode);
    }
}