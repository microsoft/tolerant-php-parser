<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class Parameter extends Node {
    /** @var Token */
    public $type;
    /** @var Token */
    public $byRefToken;
    /** @var Token */
    public $variableName;
    /** @var Token */
    public $openParen;
    /** @var DelimitedList */
    public $parameters;
    /** @var Token */
    public $closeParen;
    /** @var Type */
    public $returnTypeOpt;

    public function __construct(int $a = 3) {
        parent::__construct(NodeKind::FunctionNode);
    }
}